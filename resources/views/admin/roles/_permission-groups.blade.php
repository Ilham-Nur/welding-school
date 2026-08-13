<div class="admin-permission-groups">
    @foreach ($permissionGroups as $group)
        <section class="admin-permission-group" data-permission-group="{{ $group['key'] }}">
            <header>
                <div>
                    <h3>{{ $group['label'] }}</h3>
                    <p>{{ $group['description'] }}</p>
                </div>
                <span>{{ $group['permissions']->count() }} izin</span>
            </header>
            <div class="admin-permissions">
                @foreach ($group['permissions'] as $permission)
                    <label class="admin-check">
                        <input
                            name="permissions[]"
                            type="checkbox"
                            value="{{ $permission->name }}"
                            @checked($selectedPermissions->contains($permission->name))
                            @disabled($disabled)
                        >
                        <span>
                            {{ $permissionLabels[$permission->name] ?? $permission->name }}
                            <small>{{ $permission->name }}</small>
                        </span>
                    </label>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
