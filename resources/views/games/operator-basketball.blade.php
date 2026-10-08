@extends('layouts.operator')

@section('content')
<div class="w-full">
    <livewire:basketball-operator :gameId="$game->id" />
</div>
@endsection
