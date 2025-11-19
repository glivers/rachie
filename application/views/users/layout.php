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
<div class="rachieContainer clearfix">
    <header>
        @section('header'):

    </header>
    <div class="content">
        <div class="left-col">
            <h2>Why you need Rachie MVC </h2>
            <ul class="arrow">
                <li>Package Management is a snap due to Composer</li>
                <li>Implements caching in Memcache or Redis, faster applications.</li>
                <li>Simple syntax, close to no learning curve.</li>
                <li>Light and best suited for extensibility</li>
            </ul>
        </div>
        @section('main')
            <ul class="circles">
                <li>
                    <div class="number">1</div>
                    <div class="wrapper">
                        <h4 class="rachie-text">To edit this page </h4>
                        <p><a href="{{ Url::link(array('home','index')) }}">application/views/index.php</a></p>
                    </div>
                </li>
                <li>
                    <div class="number">2</div>
                    <div class="wrapper">
                        <h4 class="rachie-text">Controller</h4>
                        <p><a href="{{ Url::link(array('home','index')) }}">application/controllers/HomeController</a></p>
                    </div>
                </li>
                <li class="bmarg">
                    <div class="number">3</div>
                    <div class="wrapper">
                        <h4 class="rachie-text">Read the users guider found here: </h4>
                        <p><a href="{{ Url::link(array('home','index')) }}">https://rachie.dev/documentation{{ '@{{' }}  {{ '@' }}for</a></p>
                    </div>
                </li>
            </ul>
        @endsection
        </div>

        <div>

            @section('footer'):
            
        </div>

    </div>

</div><!--EO rachieContainer-->
</body>
</html>



