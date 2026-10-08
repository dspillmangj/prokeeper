@extends('layouts.operator')

@section('content')
<div class="w-full">
    <livewire:volleyball-operator :gameId="$game->id" />
</div>
@endsection
