<?php 

?>
<html>
<head>
<title><?php echo $title ?></title>

<!-- jquery ui css -->
<link rel="stylesheet" href="<?php echo Core\Helpers\Url::assets() . 'css/bootstrap.min.css'; ?>">

<!-- jquery library -->
<script src="{{ URL::asset('assets/js/jquery-2.1.1.min.js') }}"></script>



<style>
.item {
width:400px;
 
}
 
input {
    color:#222222;
	font-family:georgia,times;
	font-size:24px;
	font-weight:normal;
	line-height:1.2em;
    color:black;
}
 
 a {
    color:#222222;
	font-family:georgia,times;
	font-size:24px;
	font-weight:normal;
	line-height:1.2em;
    color:black;
    text-decoration:none;
     
}
 
a:hover {
    background-color:#BCFC3D;
}
h1 {
	color:#000000;
	font-size:41px;
	letter-spacing:-2px;
	line-height:1em;
	font-family:helvetica,arial,sans-serif;
	border-bottom:1px dotted #cccccc;
}
 
h2 {
	color:#000000;
	font-size:34px;
	letter-spacing:-2px;
	line-height:1em;
	font-family:helvetica,arial,sans-serif;
 
} 
</style>
</head>
<body>
<h1>Giver MVC Framework</h1>

<h2>{echo $title}</h2>

<form action="<?php echo Core\Helpers\Url::base() . 'home'; ?>" method="post">
<input type="text" value="Require Composer.json" onclick="this.value=''" name="todo" class="form-control "> <input type="submit" value="Confirm" class="btn btn-primary"> 
</form>
<br/><br/>

</body>
</html>