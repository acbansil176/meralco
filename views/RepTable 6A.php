<body><br><?php
 function HRToSec($time) {
		$sec = 0;$hms = explode(":",$time);	$sec = $sec + ($hms[0] * 360);$sec = $sec + ($hms[1] * 60);$sec = $sec + ($hms[2]); return $sec;
	}
function secToHR($seconds) {
		$hours = floor($seconds / 3600);$minutes = floor(($seconds / 60) % 60);$seconds = $seconds % 60;
		return str_pad($hours, 2, '0', STR_PAD_LEFT).":".str_pad($minutes, 2, '0', STR_PAD_LEFT).":".str_pad($seconds, 2, '0', STR_PAD_LEFT);
	}	?>
<div class="container-fluid" id="amazing">
<style type="text/css">
		body { font-family:Helvetica !important; }
		table tr th, table tr td { font-family:Helvetica !important; font-size:11px; padding:5px; border-top: 1px solid #d7d7d7; border-left: 1px solid #d7d7d7; border-bottom: 1px solid #d7d7d7; border-right: 1px solid #d7d7d7; border-spacing: 0;}
</style>
	<table class="table table-condensed" width="100%"><tr><th align='left' colspan="8" style="text-align:left;font-family: Helvetica; font-size:11px; border: none;">Report Code: vhis 6A</th></tr>
		<tr><th align="center" colspan="8" style="text-align:center;font-family: Helvetica; font-size:16px; border: none;">Trunk Performance [RUI]</th></tr>
		<tr align="Right"><th colspan='8' style="text-align:right;font-family: Helvetica; font-size:11px; border: none;">Report Interval: <?php 
		if($freq=="Morning"){ echo date("m/d/Y 06:00:00 ",strtotime($df))." - ". date("m/d/Y 14:00:00",strtotime($dt));}
		elseif($freq=="Afternoon"){echo date("m/d/Y 14:00:00 ",strtotime($df))." - ". date("m/d/Y 22:00:00",strtotime($dt));}
		elseif($freq=="Graveyard"){	echo date("m/d/Y 22:00:00 ",strtotime($df))." - ". date("m/d/Y 06:00:00",strtotime($dt)); }
		elseif($freq=="Fifteen"){
			if(date("H:i",strtotime($dt))!='23:59'){echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y H:i:s",strtotime($dt));}else{echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y 23:45:00",strtotime($dt));	}
		}elseif($freq=="Thirty"){
			if(date("H:i",strtotime($dt))!='23:59'){echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y H:i:s",strtotime($dt)); }else{echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y 23:30:00",strtotime($dt)); }
		}elseif($freq=="Sixty"){
			if(date("H:i",strtotime($dt))!='23:59'){echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y H:i:s",strtotime($dt)); }else{echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y 23:00:00",strtotime($dt)); }
		}elseif($freq=="W"){$day1 = date('w',strtotime($df));$day2 = date('w',strtotime($dt));echo date('m/d/Y H:i:s ', strtotime($df .' -'.$day1.' days'))." - ". date("m/d/Y 23:59:59" ,strtotime($dt .' +'.(6-$day2).' days'));}else{ echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y H:i:s",strtotime($dt));}
		?></th></tr></table><br/>
	<table class="table table-condensed" width="100%">
		<tr style="background-color:#ccc;border:1px solid #ddd;background-color:#cacaca"><th></th><th style='text-align:center;font-family: Helvetica;font-size: 11px;'>Average Utilization per Interval</th><th style='text-align:center;font-family: Helvetica;font-size: 11px;'>Usage Time</th>
			<th style='text-align:center;font-family: Helvetica;font-size: 11px;'>Answer Delay</th><th style='text-align:center;font-family: Helvetica;font-size: 11px;'>Abandon Delay</th>
			<th style='text-align:center;font-family: Helvetica;font-size: 11px;'>Offered</th><th style='text-align:center;font-family: Helvetica;font-size: 11px;'>Answered</th>
			<th style='text-align:center;font-family: Helvetica;font-size: 11px;'>Abandoned</th></tr>
	<?php $deviceName  = [];
	if($z->num_rows() !=0){
		foreach($z->result_object() as $obj) {
			switch($freq){
					case "Fifteen":	$dateInterval = date("m/d/Y H:i:s",strtotime($obj->{'15MinsInterval'})) ." - ". date("H:i:s",strtotime("15 minutes", strtotime($obj->{'15MinsInterval'}))); break; 
					case "Thirty": $dateInterval =date("m/d/Y H:i:s",strtotime($obj->{'30MinsInterva'})) ." - ". date("H:i:s",strtotime("30 minutes",strtotime($obj->{'30MinsInterva'})));	break;
					case "Sixty": $dateInterval =date("m/d/Y H:i:s",strtotime($obj->{'60MinsInterval'})) ." - ". date("H:i:s",strtotime("60 minutes",strtotime($obj->{'60MinsInterval'})));	break;
					case "D": $dateInterval = $obj->perDate; break;
					case "W": $dateInterval = $obj->Sunday ." - ".$obj->Saturday; break;
					case "M": case "PM": $dateInterval = $obj->MONTH_NAME;break;
					case "Y": $dateInterval = $obj->YEAR; break;
					case "Morning":	case "Afternoon":case "Graveyard": $datex = explode(" - ",$obj->Service_Shift);	$dateInterval = date("m/d/Y H:i:s",strtotime($datex[0])) ." - ".date("H:i:s",strtotime($datex[1]));	break;		
			}			
			$data[$obj->origDeviceName][$dateInterval][] = $obj;
			if(!in_array($obj->origDeviceName,$deviceName)){array_push($deviceName, $obj->origDeviceName);}
		}
		foreach($deviceName as $deviceIN){
			$ctr=0;$utl = 0;$sum_ut = 0;$sum_ansd = 0;$sum_abd = 0;$sum_off = 0;$sum_ans =0;$sum_abn = 0;$days =[];$day = 0;
			echo "<tr style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'><th>".$deviceIN."</th><td colspan='7'></td></tr>";
			foreach($data[$deviceIN] as $key => $value){$ctr++;
				foreach($value as $tbldata){
					$utl = $utl + $tbldata->Utilization;$sum_ut = $sum_ut + HRToSec($tbldata->UsageTime);
					$sum_ansd = $sum_ansd + HRToSec($tbldata->AnsweredDelay);
					$sum_abd = $sum_abd + HRToSec($tbldata->AbandonedDelay);
					$sum_off = $sum_off + $tbldata->Total;
					$sum_ans = $sum_ans + $tbldata->Answered;
					$sum_abn = $sum_abn + $tbldata->Abandoned;
					if(!in_array($obj->perDate,$days)){array_push($days, $obj->perDate);$day++;}			
				}
			}
				echo "<tr><th style='text-align:left;font-family: Helvetica;font-size: 11px;'>Summary</th>";
				echo "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".number_format(($sum_ut/$ctr) ,2)."<span style='color:#fff'>`</span></td>";
				echo "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".secToHR($sum_ut)."</td>";
				echo "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".secToHR($sum_ansd)."</td>";
				echo "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".secToHR($sum_abd)."</td>";
				echo "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$sum_off."</td>";
				echo "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$sum_ans."</td>";
				echo "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$sum_abn."</td></tr>";
			foreach($data[$deviceIN] as $key => $value){						
				$ut = 0;$ansd = 0;$abd = 0;$off = 0;$ans =0;$abn = 0;
				foreach($value as $tbldata){
					$ut = $ut + HRToSec($tbldata->UsageTime);
					$ansd = $ansd + HRToSec($tbldata->AnsweredDelay);
					$abd = $abd + HRToSec($tbldata->AbandonedDelay);
					$off = $off + $tbldata->Total;
					$ans = $ans + $tbldata->Answered;
					$abn = $abn + $tbldata->Abandoned;
				}
				echo "<tr><th style='text-align:center;font-family: Helvetica;font-size: 11px;'>$key</th><th></th>";
				echo "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".secToHR($ut)."</td>";
				echo "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".secToHR($ansd)."</td>";
				echo "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".secToHR($abd)."</td>";
				echo "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$off."</td>";
				echo "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$ans."</td>";
				echo "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$abn."</td></tr>";
			}
		}		
	}?>
	</table><br><br>
	<table><tr><td style='text-align:center;font-family: Helvetica;font-size: 11px; border: none;'>Printed By:<?php echo ($this->input->post('fullname') != "" ? $this->input->post('fullname'):$this->session->userdata('Fullname'))  . ': ' . date('m/d/Y H:i:s'); ?></td></tr></table>
</div>
<div id="css" style="display:none;">
	#vhis06 tr th {	font-family: Helvetica;	text-align:center;	vertical-align: middle;	text-decoration:underline; font-weight:bold; color:#000; font-size:12px; }
	#vhis06 tr td {	font-family: Helvetica;	color:#000;	font-size:10px;	padding:5px;}
	#rcode { font-family: Helvetica; font-weight:bolder; font-size:11px; text-align:center; float:left; }
	#rdate { font-family: Helvetica; font-weight:bolder; font-size:11px; text-align:right; float:right; }
	#rtitle { font-family: Helvetica; font-size:12px; font-weight:bolder; text-align:center; margin:0 0 30px; }
	#rtitle p { font-family: Helvetica; font-size:24px; font-weight:bold; text-align:center; margin:20px 0 30px; }
	#dataResult { font-family: Helvetica;text-align:center; }
	#dataSummary { font-family: Helvetica;text-align:center;background-color:#eee;font-size:14px !important;font-weight:bold; }
	#footer { font-family: Helvetica;padding:20px 0; }
	#printer { font-family: Helvetica;font-size:13px; }
</div>
	<div class="container" style="<?php  echo ($this->input->post("format") <> "" ? "display: none;" : "" ); ?>">
			<div class="content">
				<br/><br/><h3>Export:</h3>
				<div id="buttons">
					<input type="button" class="btn btn-success" onclick="exportPDF();" value="PDF"> <input type="button" class="btn btn-success" onclick="exportXLS()" value="EXCEL"> <input type="button" class="btn btn-success" onclick="exportDOC();" value="WORD"> <input type="button" class="btn btn-success" onclick="exportCSV()" value="CSV"> <input type="button" class="btn btn-success" onclick="exportHTML();" value="HTML">
				</div>
				<form id="exportPDF" action="<?php echo base_url("index.php/report_controller_z/generatePDF"); ?>" method="post" target="_blank"><textarea id="pdfdata" name="html" style="display:none"></textarea><input type="hidden" name="orient" value='1'></form>
				<form id="exportXLS" action="<?php echo base_url("index.php/report_controller_z/generateXLS"); ?>" method="post" target="_blank"><textarea id="exceldata" name="html" style="display:none"></textarea><textarea id="excelcss" name="css" style="display:none"></textarea><input type="hidden" name="orient" value='1'></form>
				<form id="exportDOC" action="<?php echo base_url("index.php/report_controller_z/generateDOC"); ?>" method="post" target="_blank"><textarea id="docdata" name="html" style="display:none"></textarea><input type="hidden" name="orient" value='1'><textarea id="htmlcss" name="css" style="display:none"></textarea></form>
				<form id="exportHTML" action="<?php echo base_url("index.php/report_controller_z/generateHTML"); ?>" method="post" target="_blank"><textarea id="htmldata" name="html" style="display:none"></textarea></form>
			</div>
				<form id="exportXLSServer" action="<?php echo base_url("index.php/report_controller_z/generateXLSServer"); ?>" method="post"><textarea id="exceldataserver" name="html" style="display:none"></textarea><textarea id="excelcssserver" name="css" style="display:none"></textarea></form>
				<form id="exportPDFServer" action="<?php echo base_url("index.php/report_controller_z/generatePDFServer"); ?>" method="post"><textarea id="pdfdataserver" name="html" style="display:none"></textarea></form>
				<form id="exportDOCServer" action="<?php echo base_url("index.php/report_controller_z/generateDOCServer"); ?>" method="post"><textarea id="docdataserver" name="html" style="display:none"></textarea><textarea id="htmlcssserver" name="css" style="display:none"></textarea></form>
				<form id="exportHTMLServer" action="<?php echo base_url("index.php/report_controller_z/generateHTMLServer"); ?>" method="post"><textarea id="htmldataserver" name="html" style="display:none"></textarea></form>
				<form id="exportCSVServer" action="<?php echo base_url("index.php/report_controller_z/generateCSVServer"); ?>" method="post"><textarea id="csvdataserver" name="html" style="display:none"></textarea></form>
				<div id="lol" style="display:none;"></div>					
	</div>
</body>
<script type="text/javascript">
	$(document).ready( function () {
		<?php if($this->input->post('scheduled') == true): ?>
		<?php if($this->input->post('format') == "PDF"): ?>
			$("#lol").text($("#amazing").html());$("#pdfdataserver").text($("#lol").text());
			setTimeout(function() {	$("#exportPDFServer").submit();	},500);
		<?php elseif($this->input->post('format') == "WORD"): ?>
			$("#lol").text($("#amazing").html());$("#docdataserver").text($("#lol").text()); $("#htmlcssserver").text($("#css").text());
			setTimeout(function() {	$("#exportDOCServer").submit();	},500);
		<?php elseif($this->input->post('format') == "HTML"): ?>
			$("#lol").text($("#amazing").html());$("#htmldataserver").text($("#lol").text());
			setTimeout(function() {	$("#exportHTMLServer").submit();},500);
		<?php elseif($this->input->post('format') == "CSV"): ?>
			var csv = [];
			var rows = document.querySelectorAll("table tr");
			for (var i = 0; i < rows.length; i++) {
			var row = [], cols = rows[i].querySelectorAll("td, th");
			
				for (var j = 0; j < cols.length; j++) {
					var str = cols[j].innerText;
					if (str.includes('`')){
					row.push('="' + cols[j].innerText.replace("`","") + '"');
					}else if (str.includes(',')){
					row.push('="' + cols[j].innerText.replace(/,/g," ") + '"');
					}else{
					row.push('="' + cols[j].innerText + '"');
					}
				}
			csv.push(row.join(","));        
			}
			var finaldata = csv.join("\n");
			$("#csvdataserver").text(finaldata);
			setTimeout(function() {	$("#exportCSVServer").submit();	},500);
		<?php else: ?>
			$("#lol").text($("#amazing").html()); $("#exceldataserver").text($("#lol").text());	$("#excelcssserver").text($("#css").text());
			setTimeout(function() {	$("#exportXLSServer").submit();	},500);
		<?php endif;endif; ?>	
	});
	function exportCSV() {
		exportTableToCSV('Report'+<?php echo date("YmdHis"); ?>+'.csv')
	}
	function exportXLS() {
		$("#lol").text($("#amazing").html());$("#exceldata").text($("#lol").text());$("#excelcss").text($("#css").text());setTimeout(function() {$("#exportXLS").submit();},500);
	}
	function exportPDF() {$("#lol").text($("#amazing").html());$("#pdfdata").text($("#lol").text());setTimeout(function() {	$("#exportPDF").submit();},500);
	}
	function exportDOC() {$("#lol").text($("#amazing").html());$("#docdata").text($("#lol").text());$("#htmlcss").text($("#css").text());setTimeout(function() {$("#exportDOC").submit();},500);
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
	function exportHTML() {$("#lol").text($("#amazing").html());$("#htmldata").text($("#lol").text());setTimeout(function() { $("#exportHTML").submit(); },500);
	}
	function exportTableToCSV(filename) {
		var csv = [];
			var rows = document.querySelectorAll("table tr");
			for (var i = 0; i < rows.length; i++) {
			var row = [], cols = rows[i].querySelectorAll("td, th");
			
			for (var j = 0; j < cols.length; j++) {
				var str = cols[j].innerText;
				if (str.includes('`')){
				row.push('="' + cols[j].innerText.replace("`","") + '"');
				}else if (str.includes(',')){
				row.push('="' + cols[j].innerText.replace(/,/g," ") + '"');
				}else{
				row.push('="' + cols[j].innerText + '"');
				}
			}
			csv.push(row.join(","));        
		}
			
		downloadCSV(csv.join("\n"), filename);
	}
</script>
