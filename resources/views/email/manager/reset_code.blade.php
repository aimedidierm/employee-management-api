<div>
    <h4>Dear, {{ $manager->name }}</h4>
    <p>
        You requested to reset your password. Click <a href="{{ $link }}">here</a> to visit the reset page. If the link
        is not clickable, you can use this URL: {{ $link }}
    </p>
    <p>
        Note that this link will expire on {{ $manager->reset_code_expires_in->format("Y-m-d h:i:s A") }}.
    </p>
    <br />
    <p>
        If you did not request this, please change your password immediately, as someone might be attempting to access
        your account.
    </p>
</div>