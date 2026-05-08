<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TCExamCrudController extends Controller
{
    public function tables(): View
    {
        return view('data.tables', [
            'tables' => $this->allowedTables(),
            'displayName' => fn (string $t) => $this->tableDisplayName($t),
        ]);
    }

    public function index(string $table): View
    {
        $table = $this->guardTable($table);
        $primaryKeys = $this->primaryKeys($table);
        $rows = DB::table($table)->limit(100)->get();

        return view('data.index', [
            'table' => $table,
            'tableLabel' => $this->tableDisplayName($table),
            'columns' => Schema::getColumnListing($table),
            'columnLabel' => fn (string $c) => $this->columnDisplayName($c),
            'rows' => $rows,
            'primaryKeys' => $primaryKeys,
            'singlePrimaryKey' => count($primaryKeys) === 1 ? $primaryKeys[0] : null,
        ]);
    }

    public function create(string $table): View
    {
        $table = $this->guardTable($table);
        $columns = $this->columnMeta($table);
        $pk = $this->singlePrimaryKey($table);
        $foreignOptions = $this->foreignOptions($table);

        return view('data.form', [
            'mode' => 'create',
            'table' => $table,
            'tableLabel' => $this->tableDisplayName($table),
            'columns' => $columns,
            'columnLabel' => fn (string $c) => $this->columnDisplayName($c),
            'primaryKey' => $pk,
            'foreignOptions' => $foreignOptions,
            'row' => null,
        ]);
    }

    public function store(Request $request, string $table): RedirectResponse
    {
        $table = $this->guardTable($table);
        abort_unless($request->user()?->can('button.data.table.create.create_record'), 403);
        abort_unless($request->user()?->can('db.' . $table . '.insert'), 403);

        $columns = $this->columnMeta($table);
        $pk = $this->singlePrimaryKey($table);
        $payload = $this->extractPayload($request, $columns, $pk, true);

        DB::table($table)->insert($payload);

        return redirect()->route('data.table.index', ['table' => $table])->with('status', 'Record created.');
    }

    public function edit(string $table, string $id): View
    {
        $table = $this->guardTable($table);
        $pk = $this->singlePrimaryKey($table);
        abort_if(! $pk, 422, 'Edit is available only for tables with a single primary key.');

        $row = DB::table($table)->where($pk, $id)->first();
        abort_if(! $row, 404);

        return view('data.form', [
            'mode' => 'edit',
            'table' => $table,
            'tableLabel' => $this->tableDisplayName($table),
            'columns' => $this->columnMeta($table),
            'columnLabel' => fn (string $c) => $this->columnDisplayName($c),
            'primaryKey' => $pk,
            'foreignOptions' => $this->foreignOptions($table),
            'row' => $row,
        ]);
    }

    public function update(Request $request, string $table, string $id): RedirectResponse
    {
        $table = $this->guardTable($table);
        abort_unless($request->user()?->can('button.data.table.edit.update_record'), 403);
        abort_unless($request->user()?->can('db.' . $table . '.update'), 403);

        $pk = $this->singlePrimaryKey($table);
        abort_if(! $pk, 422, 'Update is available only for tables with a single primary key.');

        $payload = $this->extractPayload($request, $this->columnMeta($table), $pk, false);
        DB::table($table)->where($pk, $id)->update($payload);

        return redirect()->route('data.table.index', ['table' => $table])->with('status', 'Record updated.');
    }

    public function destroy(Request $request, string $table, string $id): RedirectResponse
    {
        $table = $this->guardTable($table);
        abort_unless($request->user()?->can('button.data.table.index.delete_record'), 403);
        abort_unless($request->user()?->can('db.' . $table . '.delete'), 403);

        $pk = $this->singlePrimaryKey($table);
        abort_if(! $pk, 422, 'Delete is available only for tables with a single primary key.');

        DB::table($table)->where($pk, $id)->delete();

        return back()->with('status', 'Record deleted.');
    }

    private function allowedTables(): array
    {
        return collect(Schema::getTableListing())
            ->map(function (string $t): string {
                return str_contains($t, '.') ? Str::afterLast($t, '.') : $t;
            })
            ->filter(fn (string $t) => Str::startsWith($t, ['tce_', 'tcexam_']))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function guardTable(string $table): string
    {
        abort_unless(in_array($table, $this->allowedTables(), true), 404);
        return $table;
    }

    private function columnMeta(string $table): array
    {
        $meta = DB::select("PRAGMA table_info('$table')");
        return array_map(function ($c) {
            return [
                'name' => $c->name,
                'type' => strtolower((string) $c->type),
                'notnull' => (int) $c->notnull === 1,
                'pk' => (int) $c->pk > 0,
            ];
        }, $meta);
    }

    private function primaryKeys(string $table): array
    {
        return collect($this->columnMeta($table))->filter(fn ($c) => $c['pk'])->pluck('name')->values()->all();
    }

    private function singlePrimaryKey(string $table): ?string
    {
        $keys = $this->primaryKeys($table);
        return count($keys) === 1 ? $keys[0] : null;
    }

    private function extractPayload(Request $request, array $columns, ?string $primaryKey, bool $create): array
    {
        $payload = [];
        foreach ($columns as $column) {
            $name = $column['name'];
            if ($primaryKey === $name && $create) {
                continue;
            }

            if (! $request->exists($name)) {
                continue;
            }

            $value = $request->input($name);
            if ($value === '' && ! $column['notnull']) {
                $payload[$name] = null;
                continue;
            }

            if (str_contains($column['type'], 'int')) {
                $payload[$name] = ($value === '' || $value === null) ? null : (int) $value;
            } elseif (str_contains($column['type'], 'bool') || str_contains($column['type'], 'tinyint')) {
                $payload[$name] = in_array((string) $value, ['1', 'true', 'on'], true) ? 1 : 0;
            } else {
                $payload[$name] = $value;
            }
        }

        return $payload;
    }

    private function foreignOptions(string $table): array
    {
        $options = [];
        $pragmaFks = DB::select("PRAGMA foreign_key_list('$table')");
        foreach ($pragmaFks as $fk) {
            $options[$fk->from] = $this->lookupOptions($fk->table, $fk->to);
        }

        foreach (Schema::getColumnListing($table) as $column) {
            if (! str_ends_with($column, '_id') || isset($options[$column])) {
                continue;
            }
            $candidate = $this->findTableByKey($column);
            if ($candidate) {
                $options[$column] = $this->lookupOptions($candidate, $column);
            }
        }

        return $options;
    }

    private function findTableByKey(string $keyColumn): ?string
    {
        foreach ($this->allowedTables() as $table) {
            $cols = Schema::getColumnListing($table);
            if (in_array($keyColumn, $cols, true)) {
                return $table;
            }
        }
        return null;
    }

    private function lookupOptions(string $table, string $key): array
    {
        $cols = Schema::getColumnListing($table);
        if (! in_array($key, $cols, true)) {
            return [];
        }

        $labelCol = collect(['name', 'title', 'user_name', 'module_name', 'subject_name', 'test_name', 'group_name'])
            ->first(fn ($c) => in_array($c, $cols, true)) ?? $key;

        return DB::table($table)
            ->select([$key, $labelCol])
            ->limit(200)
            ->get()
            ->map(fn ($row) => ['value' => $row->{$key}, 'label' => (string) $row->{$labelCol}])
            ->all();
    }

    private function tableDisplayName(string $table): string
    {
        $name = Str::replaceFirst('tcexam_', '', $table);
        $name = Str::replaceFirst('tce_', '', $name);
        return Str::of($name)->replace('_', ' ')->title()->toString();
    }

    private function columnDisplayName(string $column): string
    {
        $map = [
            'module_name' => 'Module Name',
            'module_enabled' => 'Module Active',
            'subject_name' => 'Subject Name',
            'subject_description' => 'Subject Description',
            'question_description' => 'Question Text',
            'question_explanation' => 'Question Explanation',
            'answer_description' => 'Answer Text',
            'answer_isright' => 'Correct Answer',
            'test_name' => 'Exam Name',
            'test_description' => 'Exam Description',
            'test_begin_time' => 'Start Date',
            'test_end_time' => 'End Date',
            'test_duration_time' => 'Duration (minutes)',
            'test_score_threshold' => 'Pass Threshold',
            'user_name' => 'Username',
            'user_email' => 'Email',
            'group_name' => 'Group Name',
        ];
        if (isset($map[$column])) {
            return $map[$column];
        }

        return Str::of($column)->replace('_', ' ')->title()->toString();
    }
}
