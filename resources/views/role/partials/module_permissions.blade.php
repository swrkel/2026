 {{-- !empty() before count(): on the Add Role page $module_permissions is
      not set, and count(null) is a fatal TypeError in PHP 8 - which is why
      /roles/create returned a 500 while /roles/{id}/edit worked. The next
      lines already guard $role_permissions the same way. --}}
 @if(!empty($module_permissions) && count($module_permissions) > 0)
  @php
    $module_role_permissions = [];
    if(!empty($role_permissions)) {
      $module_role_permissions = $role_permissions;
    }
    
    // Custom section labels for modules
    $module_labels = [
      'Membership' => 'Membership Module',
    ];
  @endphp
  @foreach($module_permissions as $key => $value)
  {{-- Show all module permissions regardless of module enablement status --}}
  <hr>
  <div class="row check_group">
    <div class="col-md-1">
      <h4><label>{{ $module_labels[$key] ?? $key }}</label></h4>
    </div>
    <div class="col-md-2">
      <div class="checkbox">
        <label>
          <input type="checkbox" class="check_all input-icheck"> {{ __( 'role.select_all' ) }}
        </label>
      </div>
    </div>
    <div class="col-md-9">
      @php $permission_subsection = null; @endphp
      @foreach($value as $module_permission)
      @if(isset($module_permission['section']) && $module_permission['section'] !== $permission_subsection)
      @php $permission_subsection = $module_permission['section']; @endphp
      <div class="col-md-12">
        <h5 class="text-muted" style="margin-top: 12px; margin-bottom: 6px;">{{ $module_permission['section'] }}</h5>
      </div>
      @endif
      @if(isset($module_permission['value']) && isset($module_permission['label']))
      @php
        if(empty($role_permissions) && isset($module_permission['default']) && $module_permission['default']) {
          $module_role_permissions[] = $module_permission['value'];
        }
      @endphp
      <div class="col-md-12">
        <div class="checkbox">
          <label>
            {!! Form::checkbox('permissions[]', $module_permission['value'], in_array($module_permission['value'], $module_role_permissions), 
            [ 'class' => 'input-icheck']); !!} {{ $module_permission['label'] }}
          </label>
        </div>
      </div>
      @endif
      @endforeach
    </div>
  </div>
  @endforeach
@endif