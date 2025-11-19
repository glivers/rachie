@extends('users.layout')

    @section('header')
        <img src="{{ Url::assets('img/logo.png') }}" alt="rachie logo" class="rachieLogo">
        <div class="headerText">
            <h1>Welcome to Rachie<br><span class="subtext"> MVC at it’s finest...</span> </h1>

            <p>Request Time = {{$request_time}} Rachie is a powerful open-source PHP framework with a very small footprint. Was made to be a simple and elegant toolkit, enabling rapid application development of both web sites and web applications.
            </p>
        </div>
    @endsection


@section('main')
    @parent
@endsection