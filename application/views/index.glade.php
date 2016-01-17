<!DOCTYPE html>
<html lang="en-US">
<head profile="http://www.w3.org/2005/10/profile">
<link rel="icon" type="image/ico" href="{{ Url::assets('img/logo.png') }}">

    <meta charset="UTF-8">
    <title>{{$title}}</title>
    <link href='http://fonts.googleapis.com/css?family=Raleway:400,700,600' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
     <!--    LOAD CUSTOM STYLES    -->
    <link rel="stylesheet" href="">
    <link rel="stylesheet" href="{{ Url::assets('css/style.css') }}">
    
</head>
<body>
<div class="gliverContainer clearfix">
    <header>
        <img src="{{ Url::assets('img/logo.png') }}" alt="gliver logo" class="gliverLogo">
        <div class="headerText">
            <h1>Welcome to Gliver<br><span class="subtext">MVC at it’s finest...</span> </h1>

            <p>Request Time = {{$request_time}} Gliver is a powerful open-source PHP framework with a very small footprint.
                Was made to be a simple and elegant toolkit, enabling rapid application development
                of both web sites and web applications.
            </p>
        </div>
    </header>
    <div class="content">
        <div class="left-col">
            <h2>Why you need Gliver MVC </h2>
            <ul class="arrow">
                <li>Package Management is a snap due to Composer</li>
                <li>Implements caching in Memcache or Redis, faster applications.</li>
                <li>Simple syntax, close to no learning curve.</li>
                <li>Light and best suited for extensibility</li>
            </ul>
        </div>

@include('subview')

            <ul class="circles">
                <li>
                    <div class="number">1</div>
                    <div class="wrapper">
                        <h4 class="gliver-text">To edit this page </h4>
                        <p><a href="{{ Url::link(array('home','index')) }}">application/views/index.php</a></p>
                    </div>
                </li>
                <li>
                    <div class="number">2</div>
                    <div class="wrapper">
                        <h4 class="gliver-text">Controller</h4>
                        <p><a href="{{ Url::link(array('home','index')) }}">application/controllers/HomeController</a></p>
                    </div>
                </li>
                <li class="bmarg">
                    <div class="number">3</div>
                    <div class="wrapper">
                        <h4 class="gliver-text">Read the users guider found here: </h4>
                        <p><a href="{{ Url::link(array('home','index')) }}">https://getgliver.com/documentation{{ '@{{' }}  {{ '@' }}for</a></p>
                    </div>
                </li>
            </ul>
        </div>

    </div>

</div><!--EO gliverContainer-->
</body>
</html>



