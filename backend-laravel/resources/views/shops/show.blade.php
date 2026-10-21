@extends('layouts.app')

@section('content')
<div style="background-color: #FAF8F5; min-height: 100vh;">
    <x-shop-client :id="$id" />
</div>
@endsection

