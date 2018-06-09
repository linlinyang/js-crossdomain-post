# 利用iframe对form表单（含文件上传）进行跨任何域提交，无刷新且可以返回值

## 常见跨域方法
1. 利用jsonp跨域，jquery已经封装好了；`缺点：不能跨域上传文件`
2. 利用form的action提交表单；`缺点：刷新当前页面，交互差`
3. 设置form的action并设置target指向一个隐藏的iframe；`缺点：无法获取返回值`

##做个测试,我这里没有隐藏iframe以便查看结果
###client.html
```html
<form action="http://localhost/js/server.php" method="post" enctype="multipart/form-data" target="myframe" name="myform">
		
	<input type="text" name="uname">
	<input type="file" name="imgs[]" multiple="multiple">
	<textarea name="desc"></textarea>
	<input type="submit" name="submit">

</form>

<iframe id="myframe" name="myframe" width="300" height="300" ></iframe>
```
###如果服务器有返回值的话那么数据应该是返回到这里的iframe，而当前页面是无法获取到子iframe里面的元素除非设置iframe和当前页面是一个域，这样的话虽然可以无刷新提交，但是在跨域的情况下还是无法获取服务端返回结果
###所以有的人就在服务端返回数据的时候，设置iframe的域名和client的域名相同，然后就可以读取iframe里面的内容了。但是这个跨域相当局限，只能在域名和子域名下进行跨域提交。那么有没有办法让client和子iframe进行跨域通讯呢！答案是：有！

----------

###postMessage可以向包含在当前页面的iframe或者由当前页面打开的窗口传递数据，兼容性可以兼容至IE5，详情可查看[https://developer.mozilla.org/zh-CN/docs/Web/API/Window/postMessage](https://developer.mozilla.org/zh-CN/docs/Web/API/Window/postMessage "MDN文档")。
###form提交后，监听iframe的load事件，然后向iframe发送消息，请求返回数据；在服务端就可以监听message事件，然后返回客户端所需要返回的数据；然后在客户端监听message事件，实现client和iframe（伪客户端）的通信；代码如下：
###客户端js
```js
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

			})(),
			unlisten = (function(){

				if(window.removeEventListener){
					return function(ele,type,handler){
						ele.removeEventListener(type,handler);
					}
				}else if(window.detachEvent){
					return function(ele,type,handler){
						ele.detachEvent('on'+type,handler);
					}
				}else{
					ele['on'+type] = null;
				}

			})();

		function messageGet(e){//get message from iframe
			if(e.origin == 'http://localhost'){
				var data = JSON.parse(e.data);
				console.log(data);

			}
		}

		function frameLoad(e){//form has submit

			e = e || window.event;
			var target = e.target || e.srcElement;//the iframe
			console.log('frame loaded');

			target.contentWindow.postMessage('getData','http://localhost');//post message to the iframe
			target = null;
		}

		function checkform(){
			/*
			*do something to verify form
			*/
			return true;
		}

		function formSubmit(e){
			e = e || window.event;

			if(!checkform()){
				if(e.prevendDefault && e.cancelable){
					e.prevendDefault();
				}else{
					e.returnValue && (e.returnValue = false);
				}
			}

		}

		listen(window,'load',windowLoad);
		function windowLoad(){

			listen(window,'message',messageGet);
			listen(document.getElementById('myframe'),'load',frameLoad);
			listen(document.forms['myform'],'submit',formSubmit);

		}

		listen(window,'unload',function(){
			unlisten(window,'message',messageGet);
			unlisten(document.getElementById('myframe'),'load',frameLoad);
			unlisten(document.forms['myform'],'submit',formSubmit);
			listen = unlisten = null;
		});

	</script>
```
###服务端
```php
<?php 

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
```
###返回的结果写入至iframe，让client和iframe通讯，获取返回结果；`注意，IE9及以下postMessage无法发送Object对象，所以只能用json压缩成字符串然后返回`

----------
至此，跨域上传文件完成
## client代码和server代码已上传，亲测可用，兼容IE5+、chrome、firfox、opera、safari ##


