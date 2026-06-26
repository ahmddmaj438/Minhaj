<?php

namespace App\Http\Controllers;

use App\Support\FriendlyName;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TCExamCrudController extends Controller
{
    public function tables(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $tables = collect($this->allowedTables())
            ->when($search !== '', function ($tables) use ($search) {
                return $tables->filter(function (string $table) use ($search): bool {
                    $section = FriendlyName::dataSection($table);

                    return str_contains(Str::lower($section['label']), Str::lower($search))
                        || str_contains(Str::lower($section['group']), Str::lower($search))
                        || str_contains(Str::lower($section['description']), Str::lower($search));
                });
            })
            ->map(fn (string $table): array => [
                'key' => $table,
                ...FriendlyName::dataSection($table),
            ])
            ->groupBy('group')
            ->sortKeys();

        return view('data.tables', [
            'tables' => $tables,
            'search' => $search,
            'totalTables' => count($this->allowedTables()),
            'shownTables' => $tables->flatten(1)->count(),
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
            'tableDescription' => FriendlyName::dataSection($table)['description'],
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
        $columns = $this->formFields($table);
        $pk = $this->singlePrimaryKey($table);
        $foreignOptions = $this->foreignOptions($table);

        return view('data.form', [
            'mode' => 'create',
            'table' => $table,
            'tableLabel' => $this->tableDisplayName($table),
            'tableDescription' => FriendlyName::dataSection($table)['description'],
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

        $columns = $this->formFields($table);
        $pk = $this->singlePrimaryKey($table);
        $this->validateDynamicPayload($request, $columns, $pk, true);
        $payload = $this->extractPayload($request, $columns, $pk, true);

        $this->ensurePayloadIsNotEmpty($payload);

        DB::transaction(fn () => DB::table($table)->insert($payload));

        return redirect()->route('data.table.index', ['table' => $table])->with('status', 'New information was added.');
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
            'tableDescription' => FriendlyName::dataSection($table)['description'],
            'columns' => $this->formFields($table),
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
        abort_if(! $pk, 422, 'Changes can be saved only for data sections with one clear reference number.');
        abort_if(! DB::table($table)->where($pk, $id)->exists(), 404);

        $columns = $this->formFields($table);
        $this->validateDynamicPayload($request, $columns, $pk, false);
        $payload = $this->extractPayload($request, $columns, $pk, false);
        $this->ensurePayloadIsNotEmpty($payload);

        DB::transaction(fn () => DB::table($table)->where($pk, $id)->update($payload));

        return redirect()->route('data.table.index', ['table' => $table])->with('status', 'Changes were saved.');
    }

    public function destroy(Request $request, string $table, string $id): RedirectResponse
    {
        $table = $this->guardTable($table);
        abort_unless($request->user()?->can('button.data.table.index.delete_record'), 403);
        abort_unless($request->user()?->can('db.' . $table . '.delete'), 403);

        $pk = $this->singlePrimaryKey($table);
        abort_if(! $pk, 422, 'This information cannot be removed here because it does not have one clear reference number.');
        abort_if(! DB::table($table)->where($pk, $id)->exists(), 404);

        DB::transaction(fn () => DB::table($table)->where($pk, $id)->delete());

        return back()->with('status', 'Information was removed from the system.');
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
                'default' => $c->dflt_value,
            ];
        }, $meta);
    }

    private function formFields(string $table): array
    {
        return array_map(function (array $column): array {
            $name = $column['name'];
            $type = $column['type'];
            $input = $this->fieldInput($name, $type);

            return [
                ...$column,
                'label' => $this->columnDisplayName($name),
                'input' => $input,
                'required' => $column['notnull'] && ! $column['pk'],
                'full_width' => in_array($input, ['textarea', 'json'], true),
                'placeholder' => $this->fieldPlaceholder($name, $input),
                'rows' => $this->fieldRows($name, $input),
            ];
        }, $this->columnMeta($table));
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
            if (($column['input'] ?? null) === 'datetime-local') {
                $value = $this->normalizeDateTimeValue($value);
            }

            if ($value === '' && ! $column['notnull']) {
                $payload[$name] = null;
                continue;
            }

            if (($column['input'] ?? null) === 'checkbox') {
                $payload[$name] = in_array((string) $value, ['1', 'true', 'on'], true) ? 1 : 0;
            } elseif (str_contains($column['type'], 'int')) {
                $payload[$name] = ($value === '' || $value === null) ? null : (int) $value;
            } elseif (str_contains($column['type'], 'bool') || str_contains($column['type'], 'tinyint')) {
                $payload[$name] = in_array((string) $value, ['1', 'true', 'on'], true) ? 1 : 0;
            } else {
                $payload[$name] = $value;
            }
        }

        return $payload;
    }

    private function validateDynamicPayload(Request $request, array $columns, ?string $primaryKey, bool $create): void
    {
        $rules = [];
        $attributes = [];

        foreach ($columns as $column) {
            $name = $column['name'];

            if ($primaryKey === $name && $create) {
                continue;
            }

            $attributes[$name] = $column['label'];
            $fieldRules = [$column['required'] ? 'required' : 'nullable'];

            $input = $column['input'] ?? 'text';
            $type = $column['type'] ?? '';

            if ($input === 'checkbox') {
                $fieldRules[] = 'boolean';
            } elseif ($input === 'email') {
                $fieldRules[] = 'email';
                $fieldRules[] = 'max:255';
            } elseif (in_array($input, ['date', 'datetime-local'], true)) {
                $fieldRules[] = 'date';
            } elseif ($input === 'json') {
                $fieldRules[] = 'json';
            } elseif (str_contains($type, 'int')) {
                $fieldRules[] = 'integer';
            } elseif (str_contains($type, 'decimal') || str_contains($type, 'numeric') || str_contains($type, 'real') || str_contains($type, 'float')) {
                $fieldRules[] = 'numeric';
            } else {
                $fieldRules[] = 'string';
                $fieldRules[] = in_array($input, ['textarea', 'password'], true) ? 'max:10000' : 'max:255';
            }

            $rules[$name] = $fieldRules;
        }

        $request->validate($rules, [], $attributes);
    }

    private function ensurePayloadIsNotEmpty(array $payload): void
    {
        if ($payload === []) {
            throw ValidationException::withMessages([
                'record' => 'No editable values were submitted.',
            ]);
        }
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
        return FriendlyName::dataSection($table)['label'];
    }

    private function fieldInput(string $name, string $type): string
    {
        if ($this->isBooleanField($name, $type)) {
            return 'checkbox';
        }

        if (str_contains($type, 'json')) {
            return 'json';
        }

        if ($this->isLongTextField($name, $type)) {
            return 'textarea';
        }

        if (str_contains($type, 'date') || str_contains($type, 'time') || str_ends_with($name, '_at')) {
            if (str_contains($type, 'date') && ! str_contains($type, 'time') && ! str_ends_with($name, '_at')) {
                return 'date';
            }

            return 'datetime-local';
        }

        if (str_contains($type, 'int') || str_contains($type, 'decimal') || str_contains($type, 'numeric') || str_contains($type, 'real') || str_contains($type, 'float')) {
            return 'number';
        }

        if (str_contains($name, 'email')) {
            return 'email';
        }

        if (str_contains($name, 'password')) {
            return 'password';
        }

        return 'text';
    }

    private function isBooleanField(string $name, string $type): bool
    {
        return str_contains($type, 'bool')
            || str_contains($type, 'tinyint')
            || str_starts_with($name, 'is_')
            || str_starts_with($name, 'has_')
            || str_starts_with($name, 'can_')
            || str_ends_with($name, '_enabled')
            || str_ends_with($name, '_active')
            || str_ends_with($name, '_isright')
            || str_ends_with($name, '_fullscreen')
            || str_ends_with($name, '_auto_next')
            || str_ends_with($name, '_inline_answers')
            || str_ends_with($name, '_to_users')
            || str_ends_with($name, '_select')
            || str_ends_with($name, '_radio')
            || str_ends_with($name, '_partial_score')
            || str_ends_with($name, '_timeout')
            || str_ends_with($name, '_repeatable')
            || str_contains($name, '_random_answers_order')
            || str_contains($name, '_random_questions_order')
            || in_array($name, ['passed'], true);
    }

    private function isLongTextField(string $name, string $type): bool
    {
        return str_contains($type, 'text')
            || str_contains($name, 'description')
            || str_contains($name, 'explanation')
            || str_contains($name, 'comment')
            || str_contains($name, 'payload')
            || str_contains($name, 'data');
    }

    private function fieldPlaceholder(string $name, string $input): ?string
    {
        return match ($input) {
            'email' => 'name@example.com',
            'date' => 'YYYY-MM-DD',
            'datetime-local' => 'YYYY-MM-DD HH:MM',
            'json' => '{"key": "value"}',
            'password' => str_contains($name, 'test') ? 'Optional password' : null,
            default => null,
        };
    }

    private function fieldRows(string $name, string $input): int
    {
        if ($input === 'json') {
            return 8;
        }

        if (str_contains($name, 'description') || str_contains($name, 'explanation')) {
            return 5;
        }

        return $input === 'textarea' ? 4 : 1;
    }

    private function normalizeDateTimeValue(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        $value = str_replace('T', ' ', $value);

        return strlen($value) === 16 ? $value . ':00' : $value;
    }

    private function columnDisplayName(string $column): string
    {
        return FriendlyName::column($column);
    }
}
