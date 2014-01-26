<?php

	//APIキー
	$key='5620d55ba72a053ae6a5a83f29f1c7a6';
	$secret='f930f12c2b0a46d31d90daa8c88dc685';

	//表示設定
	$limit = 50; //表示数
	$order = "entry_id"; //ソート基準キー
	$sortflag = SORT_DESC; //ソート方法

	//初期化
	$data = array();
	$param1 = array();
	$param2 = array();
	$param3 = array();

	//パラメータ設定
	$resource1 = 'search';
	$param1 = array(
							'query' => 'couple',
							'max'   => 50
						);
						
	$resource2 = 'view';
	$param2 = array(
							'view' => 'everything',
							'max'  => 50
						);
						
	$resource3 = 'view';
	$param3 = array(
							'view'  	=> 'rated',
							'max'		=> 50
						);

	//多次元配列作成
	$data[0]['resource'] = $resource1;
	$data[0]['param'] = $param1;

	$data[1]['resource'] = $resource2;
	$data[1]['param'] = $param2;

	$data[2]['resource'] = $resource3;
	$data[2]['param'] = $param3;

	//メイン処理
	require_once 'blipfoto.php';
	$blip = new BlipfotoAPI($key, $secret);
	$result = $blip->get($data);

	//取得結果配列を整理
	$sort_result = array();
	foreach($result as $key => $val){
		if (is_array($val)) {
			foreach($val as $key2=> $val2){
				$sort_result[] = $val2;
			}
		}	
	}

	//並び替え(デフォルト … entry_id降順)
	$entry_id = array();
	foreach($sort_result as $key => $val){
		$entry_id[$key] = $val[$order];
	}
	array_multisort($entry_id,$sortflag,$sort_result);
	
	//表示数(デフォルト … 50)
	$limit_result = array_splice($sort_result, 0, $limit);

?>

<!DOCTYPE html>
<html lang="ja">
	<head>
		<meta charset="utf-8">
		<title>Blipfoto × Bootstrap!</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<!-- Bootstrap -->
		<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">

		<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
		<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
		<!--[if lt IE 9]>
			<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
			<script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
		<![endif]-->
		<style>body{overflow:hidden;}</style>  
	</head>
	<body>
		<h1>Blipfoto × Bootstrap!</h1>

		<?php foreach (array_chunk($limit_result, 12) as $row) { ?>
			<div class="row">
				<?php foreach($row as $val){ ?>
					<div class="col-xs-2 col-md-1"><a href="<?php echo $val['url']; ?>"><img src="<?php echo $val['thumbnail']; ?>" alt="<?php echo $val['title']; ?>" title="<?php echo $val['journal_title']; ?>"></a></div>
				<?php } ?>
			</div>
		<?php } ?>

		<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
		<script src="https://code.jquery.com/jquery.js"></script>
		<!-- Include all compiled plugins (below), or include individual files as needed -->
		<script src="bootstrap/js/bootstrap.min.js"></script>
	</body>
</html>