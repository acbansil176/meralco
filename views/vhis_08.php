<body>
<div id="amazing">
<style type="text/css">
	body  { font-family:Helvetica !important; }
	table { border-spacing: 0; cell-spacing: 0; }
	th, td {  border-top: 0.5px solid #d7d7d7;  border-left: 0.5px solid #d7d7d7;  border-bottom: 0.5px solid #d7d7d7;  border-right: 0.5px solid #d7d7d7;  padding: 0px; }
</style>
<div id="myamazing" class="container-fluid" style="float:left;">
	<table class="table table-condensed" style="width:100%">
		<tr><th align='left' colspan="17" style="text-align:left;font-family: Helvetica; font-size:11px; border:none;">Report Code: Vhis 08</th></tr>
	<tr><th align="center" colspan="17" style="text-align:center;font-family: Helvetica; font-size:16px; border:none;">Outbound Voice - Residential Customer Satisfaction Survey</th></tr>
	<tr ><th align="right" colspan="17" style="text-align:right;font-family: Helvetica; font-size:11px; border:none;">Report Interval: 
	<?php
		if ($frequency == "Morning"){ echo date("m/d/Y ",strtotime($datefrom))."06:00:00 - ". date("m/d/Y",strtotime($dateto)) . " 14:00:00"; }
		else if ($frequency == "Afternoon"){ echo date("m/d/Y ",strtotime($datefrom))."14:00:00 - ". date("m/d/Y",strtotime($dateto)) . " 22:00:00"; }
		else if ($frequency == "Graveyard"){ echo date("m/d/Y ",strtotime($datefrom))."22:00:00 - ". date("m/d/Y",strtotime($dateto)) . " 06:00:00"; }
		elseif($frequency=="Fifteen"){ if(date("H:i",strtotime($dateto))!='23:59'){echo date("m/d/Y H:i:s ",strtotime($datefrom))." - ". date("m/d/Y H:i:s",strtotime($dateto));}else{echo date("m/d/Y H:i:s ",strtotime($datefrom))." - ". date("m/d/Y 23:45:00",strtotime($dateto)); }} 
		elseif($frequency=="Thirty"){ if(date("H:i",strtotime($dateto))!='23:59'){echo date("m/d/Y H:i:s ",strtotime($datefrom))." - ". date("m/d/Y H:i:s",strtotime($dateto)); }else{echo date("m/d/Y H:i:s ",strtotime($datefrom))." - ". date("m/d/Y 23:30:00",strtotime($dateto)); }}
		elseif($frequency=="Sixty"){ if(date("H:i",strtotime($dateto))!='23:59'){echo date("m/d/Y H:i:s ",strtotime($datefrom))." - ". date("m/d/Y H:i:s",strtotime($dateto)); }else{echo date("m/d/Y H:i:s ",strtotime($datefrom))." - ". date("m/d/Y 23:00:00",strtotime($dateto)); }}
		elseif($frequency=="W"){$day1 = date('w',strtotime($datefrom));$day2 = date('w',strtotime($dateto));echo date('m/d/Y H:i:s ', strtotime($datefrom .' -'.$day1.' days'))." - ". date("m/d/Y 23:59:59" ,strtotime($dateto .' +'.(6-$day2).' days'));}
		else{ echo date("m/d/Y H:i:s ",strtotime($datefrom))." - ". date("m/d/Y H:i:s",strtotime($dateto)); } ?></th></tr></table><br/></table>
<?php
		$datefrom;$dateto;$siteArray = [];$siteIntervalArray = [];$callDispoArray = [];$frequencyCol = "";
		switch($frequency){
			case "Fifteen":$frequencyCol = "15MinsInterval";break;
			case "Thirty":$frequencyCol = "30MinsInterval";break;
			case "Sixty":$frequencyCol = "60MinsInterval";break;
			case "D":$frequencyCol = "perDate";break;
			case "W":$frequencyCol = "Sunday";break;
			case "M":break;
			case "PM":$frequencyCol = "MONTH_NAME"; break;
			case "Y":$frequencyCol = "YEAR"; break;
			case "Morning":$frequencyCol = "Morning";  break;
			case "Afternoon":$frequencyCol = "Afternoon";  break;
			case "Graveyard":$frequencyCol = "Graveyard";   break;
		}
	?>
		<?php 
			$chartDispo = [];
			if(count($records->result_array()) < 0):
				echo "no records for selected parameter";
		?>
		<?php else: ?>
			<?php
				foreach($records->result_array as $rec){
					if ($frequencyCol == 'Morning'){if ($rec['Morning'] == ""){continue;}}
					else if ($frequencyCol == 'Afternoon'){if ($rec['Afternoon'] == ""){continue;}}
					else if ($frequencyCol == 'Graveyard'){if ($rec['Graveyard'] == ""){continue;}}
					$pieCallDisposition[$rec['SiteName']][$rec['Disposition_desc']][] = $rec;
					$siteCol = $rec['SiteName'];
					if($siteCol == "" or $siteCol == null){$siteCol = "MOC";}
					if(!in_array($siteCol, $siteArray)){array_push($siteArray, $siteCol);}
					if(!in_array($rec['Disposition_desc'], $callDispoArray)){array_push($callDispoArray, $rec['Disposition_desc']);}
				}
				//echo count($records->result_array);
				sort($siteArray);
				foreach($siteArray as $sitecheck){
					$chartDispo[$sitecheck][]=$sitecheck;$temoArray = [];
					foreach($records->result_array as $rec){
						if($rec['SiteName'] == $sitecheck){array_push($temoArray, $rec['Disposition_desc']);}
					}
					array_push($chartDispo[$sitecheck], $temoArray);
				}
				$freqList = [];
				foreach($siteArray as $sitecheck){
					$siteIndex = array_search($sitecheck, $siteArray);$freqloopId = 0;
					foreach($records->result_array as $rec){
						if($sitecheck == $rec['SiteName']){
							if(!in_array($rec[$frequencyCol], $freqList)){
								if($rec[$frequencyCol] <> ""){
									array_push($freqList, $rec[$frequencyCol]);  // add each record of  interval to assigned Site
									$siteIntervalArray[$siteIndex][$freqloopId] = $rec[$frequencyCol];
									$freqloopId  = $freqloopId + 1;
								}
							}
						}
					}
				}
				sort($siteIntervalArray);
			?>
			<?php foreach($siteArray as $site){ 
				$temoArray2=[];
			?>
					<table class="table table-condensed" style="font-family:Helvetica; font-size:11px; border:1px solid #d7d7d7; border-spacing:0;" width="100%" cellspacing=0 cellpadding=0>
						<tr style="">
							<td style="font-weight: bold;border: 1px solid #d7d7d7 ;background-color:#36CCF8" colspan="17">Site Name : <?php echo $site; ?></td>
						</tr>
						<tr style="background-color: #cacaca; ">
							<td cellspacing="0" style="border: 1px solid #d7d7d7 ; text-align:center;"><b>Interval</b></td>
							<td cellspacing="0" style="border: 1px solid #d7d7d7; text-align:center;"><b>SIN</b></td>
							<td cellspacing="0" style="border: 1px solid #d7d7d7; text-align:center;"><b>First Name</b></td>
							<td cellspacing="0" style="border: 1px solid #d7d7d7; text-align:center;"><b>Last Name</b></td>
							<td cellspacing="0" style="border: 1px solid #d7d7d7; text-align:center;"><b>Gender</b></td>
							<td cellspacing="0" style="border: 1px solid #d7d7d7; text-align:center;"><b>Service Address</b></td>
							<td cellspacing="0" style="border: 1px solid #d7d7d7; text-align:center;"><b>Telephone Number 1</b></td>
							<td cellspacing="0" style="border: 1px solid #d7d7d7; text-align:center;"><b>Telephone Number 2</b></td>
							<td cellspacing="0" style="border: 1px solid #d7d7d7; text-align:center;"><b>Mobile Number 1</b></td>
							<td cellspacing="0" style="border: 1px solid #d7d7d7; text-align:center;"><b>Mobile Number 2</b></td>
							<td cellspacing="0" style="border: 1px solid #d7d7d7; text-align:center;"><b>Report Number</b></td>
							<td cellspacing="0" style="border: 1px solid #d7d7d7; text-align:center;"><b>Agent Name</b></td>
							<td cellspacing="0" style="border: 1px solid #d7d7d7; text-align:center;"><b>Date of Resolution</b></td>
							<td cellspacing="0" style="border: 1px solid #d7d7d7; text-align:center;"><b>Time of Resolution</b></td>
							<td cellspacing="0" style="border: 1px solid #d7d7d7; text-align:center;"><b>Date of Call</b></td>
							<td cellspacing="0" style="border: 1px solid #d7d7d7; text-align:center;"><b>Time of Call</b></td>
							<td cellspacing="0" style="border: 1px solid #d7d7d7; text-align:center;"><b>Call Disposition</b></td>
						</tr>
						<?php 
							$siteIndexLocation = array_search($site, $siteArray);
							foreach($freqList as $interval){
							$lastCheckFreq = "";
							foreach($records->result_array as $intRec){ 
								switch($frequency){
									case "Fifteen":$dateIntervalplot = date("m/d/Y H:i:s", strtotime($intRec['15MinsInterval']));$adder = " - ".date("H:i:s",strtotime($intRec['15MinsInterval'] ."+15 minutes"));break;
									case "Thirty":$dateIntervalplot = date("m/d/Y H:i:s", strtotime($intRec['30MinsInterval']));$adder = " - ".date("H:i:s",strtotime($intRec['30MinsInterval'] ."+30 minutes"));break;
									case "Sixty":$dateIntervalplot = date("m/d/Y H:i:s", strtotime($intRec['60MinsInterval']));$adder = " - ".date("H:i:s",strtotime($intRec['60MinsInterval'] ."+1 Hour"));break;
									case "D":$dateIntervalplot = date("m/d/Y", strtotime($intRec['perDate']));$adder = "";break;
									case "W":$dateIntervalplot = $intRec['Sunday'];$adder =  " - ".$intRec['Saturday'];break;
									case "M":break;
									case "PM":$dateIntervalplot = $intRec['MONTH_NAME'];$adder = "";break;
									case "Y":$dateIntervalplot = $intRec['YEAR'];$adder = "";break;
									case "Morning":$dataexploder = explode(" - ",$intRec['Morning']);$dataexploder1 = explode(" ",$intRec['Morning']);$dataexploder1 = $dataexploder1[0] . " 06:00:00";$dateIntervalplot = $intRec['Morning'];$datex = (date("m/d/Y H:i:s",strtotime( $dataexploder[0])));$adder = " - ".date("m/d/Y H:i:s",strtotime( $datex ."+8 Hours"));break;
									case "Afternoon":$dataexploder = explode(" - ",$intRec['Afternoon']);$dataexploder1 = explode(" ",$intRec['Afternoon']);$dataexploder1 = $dataexploder1[0] . " 14:00:00";$dateIntervalplot = $intRec['Afternoon'];$datex = (date("m/d/Y H:i:s",strtotime( $dataexploder[0])));$adder = " - ".date("m/d/Y H:i:s",strtotime( $datex ."+8 Hours"));break;
									case "Graveyard":$dataexploder = explode(" - ",$intRec['Graveyard']);$dataexploder1 = explode(" ",$intRec['Graveyard']);$dataexploder1 = $dataexploder1[0] . " 22:00:00";$dateIntervalplot = $intRec['Graveyard'];$datex = (date("m/d/Y H:i:s",strtotime( $dataexploder[0])));$adder = " - ".date("m/d/Y H:i:s",strtotime( $datex ."+8 Hours"));break;
								}		
									if($intRec['SiteName'] == $site && $intRec[$frequencyCol] == $interval):
								?>
									<tr style="border: 1px solid #d7d7d7; text-align:center;">
										<?php
											$checkFreq = "";
											if($frequency == "D"){$checkFreq = date('m/d/Y', strtotime($interval));}
											else{$checkFreq = date('m/d/Y H:i:s', strtotime($interval));}
											if($lastCheckFreq <> $checkFreq){
												$rcount = 0;
												foreach($records->result_array as $intRec){ if($intRec['SiteName'] == $site && $intRec[$frequencyCol] == $interval){$rcount++;}}
												echo "<td style='border-top: 1px solid #d7d7d7; text-align:center;border-bottom:none !important;'>";
												if($frequency == "Morning" ||$frequency == "Afternoon" ||$frequency == "Graveyard"){echo $dataexploder1.$adder;}
												elseif($frequency == "D"){echo date('m/d/Y', strtotime($dateIntervalplot)).$adder;}
												else{echo $dateIntervalplot.$adder;}
												echo "</td>";
												$lastCheckFreq = $checkFreq;
											}else{echo "<td style='border-top:1px solid #fff !important;border-bottom:none !important;'></td>";}
											?>
										<td style="border: 1px solid #d7d7d7; text-align:center;padding:5px;vertical-align:middle;"><span style="color:#fff;">`</span><?php echo $intRec['ServiceIDNumber']; ?> </td>
										<td style="border: 1px solid #d7d7d7; text-align:center;padding:5px;vertical-align:middle;"><?php echo $intRec['FirstName']; ?> </td>
										<td style="border: 1px solid #d7d7d7; text-align:center;padding:5px;vertical-align:middle;"><?php echo $intRec['LastName']; ?> </td>
										<td style="border: 1px solid #d7d7d7; text-align:center;padding:5px;vertical-align:middle;"><?php echo $intRec['Gender']; ?> </td>
										<td style="border: 1px solid #d7d7d7; text-align:center;padding:5px;vertical-align:middle;"><?php echo $intRec['ServiceAddress']; ?> </td>
										<td style="border: 1px solid #d7d7d7; text-align:center;padding:5px;vertical-align:middle;"><span style="color:#fff;">`</span><?php echo $intRec['TeleNum1']; ?> </td>
										<td style="border: 1px solid #d7d7d7; text-align:center;padding:5px;vertical-align:middle;"><span style="color:#fff;">`</span><?php echo $intRec['TeleNum2']; ?> </td>
										<td style="border: 1px solid #d7d7d7; text-align:center;padding:5px;vertical-align:middle;"><?php echo $intRec['MobileNum1']; ?> </td>
										<td style="border: 1px solid #d7d7d7; text-align:center;padding:5px;vertical-align:middle;"><?php echo $intRec['MobileNum2'];  ?> </td>
										<td style="border: 1px solid #d7d7d7; text-align:center;padding:5px;vertical-align:middle;"><span style="color:#fff;">`</span><?php echo $intRec['ReportNum']; ?> </td>
										<td style="border: 1px solid #d7d7d7; text-align:center;padding:5px;vertical-align:middle;"><?php echo $intRec['AgentName']; ?> </td>
										<td style="border: 1px solid #d7d7d7; text-align:center;padding:5px;vertical-align:middle;"><?php echo date('m/d/y',strtotime($intRec['ResolutionDate'])); ?> </td>
										<td style="border: 1px solid #d7d7d7; text-align:center;padding:5px;vertical-align:middle;"><?php echo date('H:i:s',strtotime($intRec['ResolutionTime'])); ?> </td>
										<td style="border: 1px solid #d7d7d7; text-align:center;padding:5px;vertical-align:middle;"><?php echo date('m/d/y',strtotime($intRec['DATE_TIME'])); ?> </td>
										<td style="border: 1px solid #d7d7d7; text-align:center;padding:5px;vertical-align:middle;"><?php echo date('H:i:s',strtotime($intRec['DATE_TIME'])); ?> </td>
										<td style="border: 1px solid #d7d7d7; text-align:center;padding:5px;vertical-align:middle;"><?php echo $intRec['Disposition_desc']; ?> </td>
										<?php  array_push($temoArray2, $intRec['Disposition_desc']); ?>
									</tr>
								<?php endif; ?>
							<?php } }  ?>
					</table><br/>
					<table class="table table-condensed" cellspacing="0" border="0" style="border:none;">
					<tr><th colspan="17" style="height:600px;border:none;"><div id="tester<?php echo str_replace(" ","_",$site);?>" class="container"><div id="chartdiv<?php echo str_replace(" ","_",$site);?>" align="center;" style="width: 100%; height: 500px; "></div>		</div>
					</th></tr></table>
			<?php 
				array_push($chartDispo[$site], $temoArray2);
				} 
			endif;
			 echo '<table class="table" style="display:none;color:#fff;"><tr><th></th></tr><tr><th></th></tr></table>
						<table style="display:none;color:#fff;">
					<tr><td style="border:none;">Printed By: ' . ($this->input->post('fullname') != "" ? $this->input->post('fullname'):$this->session->userdata('Fullname'))  . ': ' . date('m/d/Y H:i:s')  . '</td></tr></table>'; 
			echo "<span style='font-family:Helvetica;font-size:11px; border:none;'>Printed By: " . $this->session->userdata('Fullname') . " : " .date('m/d/Y H:i:s') . '</span>'; 
		 ?>
</div>
</div>
<div  class="container">
	<div class="content" style="<?php  echo ($this->input->post("format") <> "" ? "display: none;" : "" ); ?>">
	<br/><br/><h3>Export:</h3>
		<div style="float:left; padding-right:10px;">
			<input type="button" class="btn btn-success" onclick="exportPDF();" value="PDF">
		<form id="exportPDF" action="<?php echo base_url("index.php/report_controller_z/generatePDF"); ?>" method="post" target="_blank">
			<textarea id="pdfdata" name="html" style="display:none"></textarea><input type="hidden" name="orient" value="1">
		</form>
		</div>
		<div style="float:left; padding-right:10px;">
			<input type="button" class="btn btn-success" onclick="exportXLS()" value="EXCEL">
		<form id="exportXLS" action="<?php echo base_url("index.php/report_controller_z/excel"); ?>" method="post" target="_blank">
			<textarea id="exceldata" name="xhtml" style="display:none"></textarea><textarea id="excelcss" name="css" style="display:none"></textarea>
		</form>
		</div>
		<div style="float:left; padding-right:10px;">
			<input type="button" class="btn btn-success" onclick="exportCSV()" value="CSV">
		</div>
	<div style="float:left; padding-right:10px;">
		<input type="button" class="btn btn-success" onclick="exportDOC();" value="WORD">
		<form id="exportDOC" action="../report_controller_z/generateDOC"" method="post" target="_blank">
			<textarea id="docdata" name="html" style="display:none"></textarea><textarea id="htmlcss" name="css" style="display:none"></textarea>
		</form>
	</div>
	<div style="float:left; padding-right:10px;">
			<input type="button" class="btn btn-success" onclick="exportHTML();" value="HTML">
			<form id="exportHTML" action="../report_controller_z/generateHTML" method="post" target="_blank">
				<textarea id="htmldata" name="html" style="display:none"></textarea>
			</form>
	</div>
		<form id="exportXLSServer" action="<?php echo base_url("index.php/report_controller_z/generateXLSServer"); ?>" method="post">
			<textarea id="exceldataserver" name="html" style="display:none"></textarea><textarea id="excelcssserver" name="css" style="display:none"></textarea>
		</form>
		<form id="exportPDFServer" action="<?php echo base_url("index.php/report_controller_z/generatePDFServer"); ?>" method="post">
			<textarea id="pdfdataserver" name="html" style="display:none"></textarea>
		</form>
		<form id="exportDOCServer" action="<?php echo base_url("index.php/report_controller_z/generateDOCServer"); ?>" method="post">
			<textarea id="docdataserver" name="html" style="display:none"></textarea><textarea id="htmlcssserver" name="css" style="display:none"></textarea>
		</form>
		<form id="exportHTMLServer" action="<?php echo base_url("index.php/report_controller_z/generateHTMLServer"); ?>" method="post">
			<textarea id="htmldataserver" name="html" style="display:none"></textarea>
		</form>
		<form id="exportCSVServer" action="<?php echo base_url("index.php/report_controller_z/generateCSVServer"); ?>" method="post">
			<textarea id="csvdataserver" name="html" style="display:none"></textarea>
		</form>
	<div id="lol" style="display:none;"></div>
	</div>
</div>	
</body>
<?php
	$chrtString = "";$chrtStrings = [];
	foreach($siteArray as $site){
		$calldispochart = "";$total = 0;$arrCount = count($chartDispo[$site][2]);$pieitemList = array_count_values($chartDispo[$site][2]);
		foreach($pieitemList as $key => $val){
			$per = round(($val/$arrCount)*100 , 2).'%'; 
			$calldispochart .= '{ "label": "'. '  '. $key .' : ' . $val .' - '.$per.' ", "data": "'.$val.'"}, ';	
		}
		$chrtStrings[$site] = $calldispochart;
	}
?>
<script>
	$('#export').click(function(){
		$('#html').val($('#amazing').html());$('#exportform').submit();
	});
	$('#xexport').click(function(){
		$('#xhtml').val($('#amazing').html());$('#xexportform').submit()
	});
	function exportXLS() {
		$("#lol").text($("#amazing").html());$("#exceldata").text($("#lol").text());$("#excelcss").text($("#css").text());
		setTimeout(function() {$("#exportXLS").submit();},500);
	}
	function exportPDF() {
		$("#lol").text($("#amazing").html());$("#pdfdata").text($("#lol").text());
		setTimeout(function() {$("#exportPDF").submit();},500);
	}
	function exportDOC() {
		$("#lol").text($("#amazing").html());$("#docdata").text($("#lol").text());$("#htmlcss").text('qqq');
		setTimeout(function() {$("#exportDOC").submit();},500);
	}
	function exportHTML() {
		$("#lol").text($("#amazing").html());$("#htmldata").text($("#lol").text());
		setTimeout(function() {$("#exportHTML").submit();},500);
	}
	function exportCSV() {
		exportTableToCSV('Report'+<?php echo date("YmdHis"); ?>+'.csv')
	}
	function downloadCSV(csv, filename) {
		var csvFile;var downloadLink;
		csvFile = new Blob([csv], {type: "text/csv"});
		downloadLink = document.createElement("a");
		downloadLink.download = filename;
		downloadLink.href = window.URL.createObjectURL(csvFile);
		downloadLink.style.display = "none";
		document.body.appendChild(downloadLink);
		downloadLink.click();
	}
	function exportTableToCSV(filename) {
		var csv = [];var rows = document.querySelectorAll("table tr");
		for (var i = 0; i < rows.length; i++) {
			var row = [], cols = rows[i].querySelectorAll("td, th");
			for (var j = 0; j < cols.length; j++) {
				var str = cols[j].innerText;
				if (str.includes('`')){row.push('="' + cols[j].innerText.replace("`","") + '"');}
				else if (str.includes(',')){ row.push('="' + cols[j].innerText.replace(/,/g," ") + '"');}
				else{ row.push('="' + cols[j].innerText + '"'); }
			}
			csv.push(row.join(","));        
		}
		downloadCSV(csv.join("\n"), filename);
	}
$(document).ready(function(){
<?php foreach($siteArray as $site){ ?>
	var data = [ <?php echo $chrtStrings[$site]; ?> ];
    $.plot('#chartdiv<?php echo str_replace(" ","_",$site);?>', data, {
    	series: {pie: {show: true,
                label: {show: true,radius: 20/20,threshold: 2,
                    formatter: function (label, series) {
                    	var element = '<div style="font-size:6pt; text-align:center;padding:2px;">' + label + '<br/>' + series.data[0][1] + '</div>';
                        return element;
                	}}}},
        legend: {show: true,margin:50}
	});
	toCanvas('chartdiv<?php echo str_replace(" ","_",$site);?>');
<?php } ?>
		<?php if($this->input->post('scheduled') == true): ?>
			<?php if($this->input->post('format') == "PDF"): ?>
				setTimeout(function() {
					$("#lol").text($("#amazing").html());$("#pdfdataserver").text($("#lol").text());$("#exportPDFServer").submit();
				},35000);
			<?php elseif($this->input->post('format') == "WORD"): ?>
				setTimeout(function() {
					$("#lol").text($("#amazing").html());$("#docdataserver").text($("#lol").text());$("#htmlcssserver").text('qqq');$("#exportDOCServer").submit();
				},35000);
			<?php elseif($this->input->post('format') == "HTML"): ?>
				setTimeout(function() {
					$("#lol").text($("#amazing").html());$("#htmldataserver").text($("#lol").text());$("#exportHTMLServer").submit();
				},35000);
			<?php elseif($this->input->post('format') == "CSV"): ?>
				var csv = [];
				var rows = document.querySelectorAll("table tr");
				for (var i = 0; i < rows.length; i++) {
					var row = [], cols = rows[i].querySelectorAll("td, th");
					for (var j = 0; j < cols.length; j++) {
						var str = cols[j].innerText;
						if (str.includes('`')){ row.push('="' + cols[j].innerText.replace("`","") + '"');}
						else if (str.includes(',')){ row.push('="' + cols[j].innerText.replace(/,/g," ") + '"');}
						else{ row.push('="' + cols[j].innerText + '"'); }    
					}
				}
				var finaldata = csv.join("\n");
				$("#csvdataserver").text(finaldata);
				setTimeout(function() {$("#exportCSVServer").submit();},35000);
			<?php else: ?>
				setTimeout(function() {
					$("#lol").text($("#amazing").html());$("#exceldataserver").text($("#lol").text());$("#excelcssserver").text($("#css").text());$("#exportXLSServer").submit();
				},35000);
			<?php endif; ?>
		<?php endif; ?>
});
function toCanvas(elemx){
		html2canvas(document.querySelector("#"+elemx)).then(canvas => {
			var b64 = canvas.toDataURL();
			$.ajax({ 
				type: "POST", 
				url: "../mojo/imagesave",
				dataType: 'text',
				data: {
					base64data : b64
				},
				success:function(d) {
					$("#"+elemx).html(d);
				}
			}); 
		});
}	
</script>
