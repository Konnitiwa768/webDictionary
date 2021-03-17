<?php echo '<' . '?xml version="1.0" encoding="utf-8"?' . '>'; ?>
<?php
$fname = 'idyer.json';
//エスケープしてprintする関数
function print_h($str)
{
    print htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

//前方一致検索
function startsWith($haystack, $needle){
    return stripos($haystack, $needle, 0) === 0;
}

//完全一致検索
function perfectHit($haystack, $needle){
    return $haystack == $needle;
}

//json読み込み
$json = file_get_contents($fname);
$json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
$json = json_decode($json,true);

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja" dir="ltr">
<head>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<meta http-equiv="Content-Style-Type" content="text/css" /> 
<meta name="Description" content="イジェール語オンライン辞書" />
<meta name="keywords" content="人工言語,辞書," />
<link rel="stylesheet" type="text/css" href="dict.css" />
<link rel="shortcut icon" href="favicon.ico" />
<link rel="icon" href="favicon.ico" />
<title>イジェール語 オンライン辞書</title>
</head>
<body>
<div class="all">
	<div id="header">
	
	<h1>イジェール語 オンライン辞書</h1>
	<ul id="menu">
		<li><a class="menu" href="https://zaslon.info/idyerin/%e8%be%9e%e6%9b%b8%e5%87%a1%e4%be%8b/">凡例</a></li>
		<li><a class="menu" href="https://zaslon.info/idyer">ホームへ戻る</a></li>
	</ul>
	<div class="dictVer">
		<p>オンライン辞書 ver:1.4.3</p>
		<?php
		date_default_timezone_set('Asia/Tokyo');
		$mod = filemtime($fname);
		print "<p>辞書更新日:".date("Y/m/d",$mod)."<br />";
		print "単語数：".count($json["words"])."</p>";
		?>
	</div>
	<?php
	$checked_1 = "";
	$checked_2 = "";
	$checked_3 = "";
	$checked_4 = "";
	$checked_5 = "";
	$checked_6 = "";
	$checked_7 = "";
	$checked_8 = "";
	
	if((isset($_GET["type"])) && ($_GET["type"] != "")) {
		switch($_GET["type"]) {
			case "word":
				$checked_1 = "checked";
			break;
			case "trans":
				$checked_2 = "checked";
			break;
			case "both":
				$checked_3 = "checked";
			break;
			case "all":
				$checked_4 = "checked";
			break;
		}
	}else{
		//デフォルトで両方検索を選択
		$checked_3 = "checked";
	}
	
	if((isset($_GET["Idf"])) && ($_GET["Idf"] != "")) {
		$checked_5 = "checked";
	}else{
		//デフォルトで空欄
	}
	
	if((isset($_GET["mode"])) && ($_GET["mode"] != "")) {
		switch($_GET["mode"]) {
			case "prt":
				$checked_6 = "checked";
				$func = "stripos";
			break;
			case "fwd":
				$checked_7 = "checked";
				$func = "startsWith";
			break;
			case "perf":
				$checked_8 = "checked";
				$func = "perfectHit";
			break;
		}
	}else{
		//デフォルトで部分一致を選択
		$checked_6 = "checked";
	}
	?>
	
	<form action="" method="GET">
		<input type="text" name="keyBox">
		<input type="submit" name="submit" value="検索">
		<input type="radio" name="type" id="c1" value="word" <?php echo $checked_1; ?>><label for="c1">見出し語検索</label>
		<input type="radio" name="type" id="c2" value="trans" <?php echo $checked_2; ?>><label for="c2">訳語検索</label>
		<input type="radio" name="type" id="c3" value="both" <?php echo $checked_3; ?>><label for="c3">見出し語・訳語検索</label>
		<input type="radio" name="type" id="c4" value="all" <?php echo $checked_4; ?>><label for="c4">全文検索</label>
		<input type="checkbox" name="Idf" id="c5" value="true" <?php echo $checked_5; ?>><label for="c5">イジェール文字表示</label>
		<input type="radio" name="mode" id="c6" value="prt" <?php echo $checked_6; ?>><label for="c6">部分一致</label>
		<input type="radio" name="mode" id="c7" value="fwd" <?php echo $checked_7; ?>><label for="c7">前方一致</label>
		<input type="radio" name="mode" id="c8" value="perf" <?php echo $checked_8; ?>><label for="c8">完全一致</label>
		<input type="hidden" name="page" value="1">
	</form>
	</div>

	<div id="main">
	<?php
	$target = "";	//タイプ指定
	$hitWordIds = array();
	$hitEntryIds = array();
	$hitAmount =0;
	$keyWords = "";
	$totalPages = 0;
	$wordNumPerPage = 40;
	//keyBoxに入力されているときのみ，$keyWordsに代入
	if (isset($_GET['keyBox'])){
	//数字が一部にでも含まれていたら$keyWordsは空になる．
		if (preg_match("/^.*[0-9].*/", $_GET['keyBox'])) {
			print "<p>検索ワードに数字を入力しないでください。数字を検索する場合は漢数字で入力してください。</p>";
		} else {
			$keyWords = preg_replace('/[　]/u', ' ', $_GET["keyBox"]);//全角スペースを半角スペースに変換
			$keyWords = preg_replace('/\s\s+/u', ' ', $keyWords);//スペース2つ以上であれば，1つに削減
			$keyWords = explode(' ',$keyWords);//スペースで区切られた検索語を分離して配列に格納
			$i = 0;
//			foreach ($keyWords as $i =>$eachKey) {
//				$keyWords[$i] = mb_strtolower($keyWords[$i],'UTF-8');//検索の便宜のため小文字にする
//			}
		}
	}
	//ここから検索部。検索の結果を格納する。
	if(empty($keyWords[0])){
		print "<p>検索ワードを入力してください。</p>";//$keyWordsが空なら警告を表示して終了する．
    }else{
	//検索対象を取得
		if (!($_GET["type"]=='word' || $_GET["type"]=='trans' || $_GET["type"]=='both' || $_GET["type"]=='all')) {
			$_SET["type"] = 'both';
		}
	//ここに検索して、内容をarrayに格納する処理を入れる。
	    $target = $_GET["type"];
		foreach ($json["words"] as $entryId =>$singleEntry){
			$isHit= 0;		//すべての検索語にヒットする場合のみisHitが1になる
			$wordId = $singleEntry["entry"]["id"];
			switch ($target){
				case "word":
					foreach ($keyWords as $eachKey){
						if ($func($singleEntry["entry"]["form"],$eachKey) !== false){
							$isHit = 1;
							break 1;
						}
					}
				break;
				case "trans":
					foreach ($keyWords as $eachKey){
						foreach ($singleEntry["translations"] as $singleTranslation){
							foreach ($singleTranslation["forms"] as $singleTranslationForm){
								if ($func($singleTranslationForm,$eachKey) !== false){
									$isHit = 1;
									break 3;
								}
							}
						}
					}
				break;
				case "both":
					foreach ($keyWords as $eachKey){
						if ($func($singleEntry["entry"]["form"],$eachKey) !== false){
							$isHit = 1;
							break 1;
						}
					}
					if ($isHit == 0){
						foreach ($keyWords as $eachKey){
							foreach ($singleEntry["translations"] as $singleTranslation){
								foreach ($singleTranslation["forms"] as $singleTranslationForm){
									if ($func($singleTranslationForm,$eachKey) !== false){
										$isHit = 1;
										break 3;
									}
								}
							}
						}
					}
				break;
				case "all":
					foreach ($keyWords as $eachKey){
						if ($func($singleEntry["entry"]["form"],$eachKey) !== false){
							$isHit = 1;
							break 1;
						}
					}
					if ($isHit == 0){
						foreach ($keyWords as $eachKey){
							foreach ($singleEntry["translations"] as $singleTranslation){
								foreach ($singleTranslation["forms"] as $singleTranslationForm){
									if ($func($singleTranslationForm,$eachKey) !== false){
										$isHit = 1;
										break 3;
									}
								}
							}
						}
						if ($isHit == 0){
							foreach ($keyWords as $eachKey){
								foreach ($singleEntry["contents"] as $singleContent){
									if ($func($singleContent["text"],$eachKey) !== false){
										$isHit = 1;
										break 2;
									}
								}
							}
						}
					}
				break;
			}
			if($isHit == 1) {
				$hitWordIds[] = $wordId;
				$hitEntryIds[]= $entryId;
			}
		}
		//ここから表示部
		$currentPageID = $_GET['page'];
		$hitAmount = count($hitWordIds);
		print('<p>');		
		if (!preg_match("/^[0-9]+$/", $_GET['page'])) {
			$currentPageID = 1;	//ページIDに数字以外を入力された場合、強制的に1とする。
		}
		$i = $wordNumPerPage*($currentPageID-1);
		if($hitAmount==0){
			print_h($_GET["keyBox"].' での検索結果：0件');
		}else{
			print_h($_GET["keyBox"].' での検索結果：'.$hitAmount."件(".($i+1)."から".min($i+$wordNumPerPage,$hitAmount)."件目)");
		}
		print("</p>");

		while ( $i < ($wordNumPerPage*$currentPageID) && $i < $hitAmount) {
		//ここに検索結果の繰り返し表示を入れる。
			print '<ul class="wordEntry">';
			if((isset($_GET["Idf"])) && ($_GET["Idf"] != "")) {
				print '<li class="wordForm"><span class="idyerin">' . $json["words"][$hitEntryIds[$i]]["entry"]["form"] . '</span>';
			}else{
				print '<li class="wordForm">' . $json["words"][$hitEntryIds[$i]]["entry"]["form"];
			}
			print '<span class="wordId">#'. $hitWordIds[$i] . '</span></li>';
			foreach ($json["words"][$hitEntryIds[$i]]["translations"] as $singleTranslation){
				print '<li><span class="wordTitle">' . $singleTranslation["title"] . '</span>';
				foreach ($singleTranslation["forms"] as $singleTranslationForm){
					print $singleTranslationForm;
					if ($singleTranslationForm !== end($singleTranslation["forms"])){
						//最後のとき以外に「、」を追加
						print '、';
					}
				}
				print '</li>';
			}
			foreach ($json["words"][$hitEntryIds[$i]]["contents"] as $singleContent){
				print '<li clas="wordContents">';
				print '<span class="wordContentTitle">' . $singleContent["title"] . '</span>' . $singleContent["text"] . '</li>';
			}
			foreach ($json["words"][$hitWordIds[$i]]["relations"] as $singleRelation){
				print '<li><span class="wordRelation">' . $singleRelation["title"] . '</span>';
				print '<a href=dict.php?keyBox=' . $singleRelation["entry"]["form"] . '&type=word&mode=perf&page=1>' . $singleRelation["entry"]["form"] . '</a><span class="wordId">#' . $singleRelation["entry"]["id"] . '</span>';
				print '</li>';
			}
			print '</ul>';
			$i++;
		}
	}
	
	//ページ送り機能

	print('<ul class="navigation">');
	if ($wordNumPerPage<$hitAmount) {
		$totalPages = ceil($hitAmount/$wordNumPerPage);
		$i = 1;
		$conWord =  implode ("+", $keyWords);//リンク作成のため，検索語を全て+で接続した形に変換
		$mode = $_GET["mode"];
//		if ($currentPageID!=1){
//			print '<li><a href=dict.php?keyBox=';
//			print_h($conWord);
//			print '&type=';
//			print_h($target);
//			if((isset($_GET["Idf"])) && ($_GET["Idf"] != ""))
//			{
//				print '&Idf=true';
//			}
//			print '&mode=';
//			print_h($mode);
//			print '&page=1>&lt;&lt;</a></li>';
//		}
		while ($i <= $totalPages) {
			print '<li';
			if ($_GET["page"] == $i){
				print ' class=currentPage';
			}
			print '>';
			if ($_GET["page"] != $i){
				print '<a href=dict.php?keyBox=';
				print_h($conWord);
				print '&type=';
				print_h($target);
				if((isset($_GET["Idf"])) && ($_GET["Idf"] != "")){
					print '&Idf=true';
				}
				print '&mode=';
				print_h($mode);
				print '&page=';
				print_h($i);
				print '>';
				print_h($i);
				print '</a>';
			}else{
				print_h($i);
			}
			print '</li>';
			$i++;
		}
//		if ($currentPageID!=$totalPages) {
//			print '<li><a href=dict.php?keyBox=';
//			print_h($conWord);
//			print '&type=';
//			print_h($target);
//			if((isset($_GET["Idf"])) && ($_GET["Idf"] != ""))
//			{
//				print '&Idf=true';
//			}
//			print '&mode=';
//			print_h($mode);
//			print '&page=';
//			print_h($currentPageID+1);
//			print '>&gt;&gt;</a></li>';
//		}
	}else{
	}
	print('</ul>');
	?>
	
	</div>
	<div id="footer">
		<p>&copy; 2010-<?php echo date('Y'); ?> Zaslon</p>
	</div>
</div>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-16151470-3', 'starlightensign.com');
  ga('require', 'displayfeatures');
  ga('send', 'pageview');

</script>
</body>
</html>