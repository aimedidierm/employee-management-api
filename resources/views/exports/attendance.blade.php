<table>
    <thead>
        <tr>
            <th>DATE</th>
            <th>CODE</th>
            <th>NAME</th>
            <th>Arrived At</th>
            <th>Left At</th>
        </tr>
    </thead>
    <tbody>
        @foreach($attendances as $attendance)
        <tr>
            <td>{{ $attendance['date'] }}</td>
            <td>{{ $attendance['code'] }}</td>
            <td>{{ $attendance['name'] }}</td>
            <td>{{ $attendance['arrived_at'] }}</td>
            <td>{{ $attendance['left_at'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>