<?php 

	var_dump($_FILES);

	$res = json_encode(array(
		'aa' => 'txt',
		'bb' => 'data',
		'time' => date("Y-m-d h:i:s",time())
	));

	//echo $res;
$html = 
<<<html
	<!DOCTYPE html>
	<html>
	<head>
		<title>return</title>
	</head>
	<body>
	
	<script type="text/javascript">
		var listen = (function(){

			if(window.addEventListener){
				return function(ele,type,handler){
					ele.addEventListener(type,handler);
				}
			}else if(window.attachEvent){
				return function(ele,type,handler){
					ele.attachEvent('on'+type,handler)
				}
			}else{
				ele['on'+type] = handler;
			}

		})();

		listen(window,'message',function(e){
			if(e.origin == 'http://bocai.com' && e.data == 'getData'){
				e.source.postMessage(JSON.stringify({$res}),'http://bocai.com');
			}

		});

	</script>


	</body>
	</html>
html;
	echo $html;

 ?>