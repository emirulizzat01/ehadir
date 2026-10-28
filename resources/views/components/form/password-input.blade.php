@props([
    'name' => 'password',
    'label' => 'Kata Laluan',
    'value' => null,
    'required' => false,
    'placeholder' => '••••••••',
    'hint' => null,
    'toggleable' => true,
])

<x-form.input
    :name="$name"
    :label="$label"
    type="password"
    :value="$value"
    :required="$required"
    :placeholder="$placeholder"
    :hint="$hint"
    :toggleable="$toggleable"
    {{ $attributes }}
/>

