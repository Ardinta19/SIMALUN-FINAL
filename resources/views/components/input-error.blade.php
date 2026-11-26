@props(['messages'])

@if ($messages)
    <ul role="alert" {{ $attributes }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
