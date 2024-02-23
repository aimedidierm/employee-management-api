<div>
    <h4>Dear {{ $employee->name }}.</h4>
    <p>
        Your attendance was successfully recorded at
        {{ $attendance->arrived_at ? 'your arrival which was on ' . $attendance->arrived_at->format('Y-m-d h:i:s A') :
        '' }}
        {{ $attendance->arrived_at && $attendance->left_at ? ' and ' : '' }}
        {{ $attendance->left_at ? 'you signed out at ' . $attendance->left_at->format('Y-m-d h:i:s A') : '' }}
    </p>
</div>