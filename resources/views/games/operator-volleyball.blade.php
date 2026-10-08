@extends('layouts.operator')

@section('content')
<div class="w-full h-full flex-1 min-h-0 flex flex-col">
    <livewire:volleyball-operator :gameId="$game->id" />
</div>
@endsection
