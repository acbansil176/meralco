<body><br>
<?php
 function HRToSec($time) {
		$sec = 0;$hms = explode(":",$time);	$sec = $sec + ($hms[0] * 360);$sec = $sec + ($hms[1] * 60);$sec = $sec + ($hms[2]); return $sec;
	}
function secToHR($seconds) {
		$hours = floor($seconds / 3600);$minutes = floor(($seconds / 60) % 60);$seconds = $seconds % 60;
		return str_pad($hours, 2, '0', STR_PAD_LEFT).":".str_pad($minutes, 2, '0', STR_PAD_LEFT).":".str_pad($seconds, 2, '0', STR_PAD_LEFT);
	}	?>
<div class="container-fluid" id="amazing">
<style>
	body { font-family:Helvetica !important; }
	table tr th, table tr td { font-family:Helvetica !important;font-size:11px;vertical-align:center }
</style><br>
	<table class="table table-condensed" style="width:100%">
		<tr align="left"><th colspan="17" style="text-align:left;font-family: Arial;font-size: 11px">Report Code: vhis 6B</th></tr>
		<tr align="Center"><th colspan="17" ><span style="font-family: Arial;font-size: 22px">Trunk Utilization</th></tr>
		<tr align="Right"><th colspan='17' style="text-align:right;font-family: Helvetica; font-size:11px; border: none;">Report Interval: <?php 
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
	<table class="table table-condensed" style="width:100%; border: 1px solid black;">
		<tr style="background-color:#cacaca"><th colspan='2' style="border: 1px solid black;">Device Name</th><th style="border: 1px solid black; text-align:center;">Capacity</th>
			<th style="border: 1px solid black; text-align:center;">Max Used</th><th style="border: 1px solid black; text-align:center;">% Utilization(Peak)</th>
			<th style="border: 1px solid black; text-align:center;">% Utilized Period</th><th style="border: 1px solid black; text-align:center;">Traffic Intensity</th>
			<th style="border: 1px solid black; text-align:center;">Lines Required (GOS = 1%) </th><th style="border: 1px solid black; text-align:center;">Variance (+/-) </th>
			<th style="border: 1px solid black; text-align:center;">Peak Period (Date)</th><th style="border: 1px solid black; text-align:center;">Peak Period (Time) </th>
			<th style="border: 1px solid black; text-align:center;">Call Volume Attempts</th><th style="border: 1px solid black; text-align:center;">Call Volume Completed</th>
			<th style="border: 1px solid black; text-align:center;">Call Volume Blocked</th><th style="border: 1px solid black; text-align:center;">Average Duration in mins</th>
			<th style="border: 1px solid black; text-align:center;">Total Duration in Hours</th><th style="border: 1px solid black; text-align:center;">ATB</th></tr>
		<?php foreach($z->result_object() as $trunkItem){ 
				if(  $trunkItem->origDeviceName=='Summary'){ echo '<tr style="border-bottom: 1px solid black;background-color:#dadada">';echo '<td  colspan="2" style="font-weight:bold;border: 1px solid black;text-align:center;">';
				}else{ echo '<tr style="border-bottom: 1px solid black;">';echo '<td  colspan="2" style="border: 1px solid black;">';}  echo $trunkItem->origDeviceName; ?></td>
				<td style="border: 1px solid black;text-align:center;"><?php echo $trunkItem->Capacity; ?></td>
				<td style="border: 1px solid black;text-align:center;"><?php echo $trunkItem->{'Max Used'}; ?></td>
				<td style="border: 1px solid black;text-align:center;"><?php echo $trunkItem->{'% Utilized (Peak)'}; ?>%</td>
				<td style="border: 1px solid black;text-align:center;"><?php echo $trunkItem->{'% Utilized (Period)'}; ?>%</td>
				<td style="border: 1px solid black;text-align:center;"><?php echo number_format($trunkItem->{'Traffic Intensity'},2); ?></td>			
				<?php $lines =((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 >= 0.001 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 0.4  ? 1 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 0.4 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 5.4 ? 2 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 5.4 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 15.7 ?3  :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 15.7 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 29.6 ? 4  :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 29.6 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 46.1 ? 5  :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 46.1 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 64.4 ? 6  :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 64.4 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 83.9 ? 7  :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 83.9 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 105 ? 8  :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 105 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 126 ? 9  :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 126 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 149 ? 10  :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 149 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 172 ? 11  :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 172 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 195 ? 12  :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 195 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 220 ? 13  :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 220 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 244 ? 14  :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 244 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 269 ? 15 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 269 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 294 ? 16 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 294 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 320 ? 17 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 320 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 346 ? 18 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 346 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 373 ? 19 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 373 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 399 ? 20 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 399 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 426 ? 21 : 
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 426 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 453 ? 22 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 453 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 480 ? 23  :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 480 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 507 ? 24  :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 507 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 535 ? 25  :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 535 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 562 ? 26 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 562 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 590 ? 27  :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 590 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 618 ? 28  :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 618 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 647 ? 29 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 647 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 675 ? 30  :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 675 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 703 ? 31 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 703 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 732 ? 32 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 732 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 780 ? 33  :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 780 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 789 ? 34 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 789 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 818 ? 35 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 818 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 847 ? 36 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 847 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 878 ? 37 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 878 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 905 ? 38 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 905 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 935 ? 39 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 935 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 964 ? 40 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 964 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 993  ? 41 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 993 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 1023  ? 42 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 1023 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 1052  ? 43 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 1052 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 1082  ? 44 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 1082 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 1112   ? 45 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 1112 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 1142  ? 46 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 1142 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 1171  ? 47 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 1171 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 1201  ? 48 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 1201 && (number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 <= 1231   ? 49 :
				((number_format($trunkItem->{'Total Duration (in hrs)'},2) * 60) /100 > 1231 ?50 : 0)))))))))))))))))))))))))))))))))))))))))))))))))); ?>
				<td style="border: 1px solid black;text-align:center;"><?php echo $lines; ?></td> 
				<td style="border: 1px solid black;text-align:center;"><?php echo $trunkItem->Capacity - $lines; ?></td>
				<td style="border: 1px solid black;text-align:center;"><?php echo date("m/d/Y",strtotime($trunkItem->{'Peak Period (Date)'})); ?></td>
				<td style="border: 1px solid black;text-align:center;"><?php echo date("H:i:s",strtotime($trunkItem->{'Peak Period (Time)'})); ?></td>
				<td style="border: 1px solid black;text-align:center;"><?php echo $trunkItem->{'Call Volume Attempt'}; ?></td>
				<td style="border: 1px solid black;text-align:center;"><?php if ($trunkItem->{'Call Volume Completed'} == "") { echo '0'; }else { echo $trunkItem->{'Call Volume Completed'}; } ?></td>
				<td style="border: 1px solid black;text-align:center;"><?php echo $trunkItem->{'Call Volume Blocked'}; ?></td>
				<td style="border: 1px solid black;text-align:center;"><?php echo number_format($trunkItem->{'Average Duration (in mins)'},2); ?></td>
				<td style="border: 1px solid black;text-align:center;"><?php echo number_format($trunkItem->{'Total Duration (in hrs)'},2); ?></td>
				<td style="border: 1px solid black;text-align:center;"><?php echo $trunkItem->ATB; ?></td></tr><?php } //end of foreach ?>
	</table><br><br>
	<table><tr><td style='text-align:center;font-family: Helvetica;font-size: 11px; border: none;'>Printed By:<?php echo ($this->input->post('fullname') != "" ? $this->input->post('fullname'):$this->session->userdata('Fullname'))  . ': ' . date('m/d/Y H:i:s'); ?></td></tr></table>
</div>	
<div class="container-fluid" style="<?php  echo ($this->input->post("format") <> "" ? "display: none;" : "" ); ?>">
	<div class="content">
		<br/><br/><h3>Export:</h3>
		<div id="buttons"><input type="button" class="btn btn-success" onclick="exportPDF();" value="PDF"> <input type="button" class="btn btn-success" onclick="exportXLS()" value="EXCEL"> <input type="button" class="btn btn-success" onclick="exportCSV()" value="CSV"> <input type="button" class="btn btn-success" onclick="exportDOC();" value="WORD"> <input type="button" class="btn btn-success" onclick="exportHTML();" value="HTML">	</div>
		<form id="exportPDF" action="<?php echo base_url("index.php/report_controller_z/generatePDF"); ?>" method="post" target="_blank"><textarea id="pdfdata" name="html" style="display:none"></textarea><input type="hidden" name="orient" value="1"></form>
		<form id="exportXLS" action="<?php echo base_url("index.php/report_controller_z/generateXLS"); ?>" method="post" target="_blank"><textarea id="exceldata" name="html" style="display:none"></textarea><textarea id="excelcss" name="css" style="display:none"></textarea></form>
		<form id="exportDOC" action="<?php echo base_url("index.php/report_controller_z/generateDOC"); ?>" method="post" target="_blank"><textarea id="docdata" name="html" style="display:none"></textarea><textarea id="htmlcss" name="css" style="display:none"></textarea></form>
		<form id="exportHTML" action="<?php echo base_url("index.php/report_controller_z/generateHTML"); ?>" method="post" target="_blank"><textarea id="htmldata" name="html" style="display:none"></textarea></form>
		<form id="exportXLSServer" action="<?php echo base_url("index.php/report_controller_z/generateXLSServer"); ?>" method="post"><textarea id="exceldataserver" name="html" style="display:none"></textarea><textarea id="excelcssserver" name="css" style="display:none"></textarea></form>
		<form id="exportPDFServer" action="<?php echo base_url("index.php/report_controller_z/generatePDFServer"); ?>" method="post"><textarea id="pdfdataserver" name="html" style="display:none"></textarea></form>
		<form id="exportDOCServer" action="<?php echo base_url("index.php/report_controller_z/generateDOCServer"); ?>" method="post"><textarea id="docdataserver" name="html" style="display:none"></textarea><textarea id="htmlcssserver" name="css" style="display:none"></textarea></form>
		<form id="exportHTMLServer" action="<?php echo base_url("index.php/report_controller_z/generateHTMLServer"); ?>" method="post"><textarea id="htmldataserver" name="html" style="display:none"></textarea></form>
		<form id="exportCSVServer" action="<?php echo base_url("index.php/report_controller_z/generateCSVServer"); ?>" method="post"><textarea id="csvdataserver" name="html" style="display:none"></textarea></form>
	</div>		
</div>
<div id="lol" style="display:none;"></div><div id="css" style="display:none;">
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
				for (var j = 0; j < cols.length; j++) 
					row.push(cols[j].innerText);
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
			for (var j = 0; j < cols.length; j++) 
				row.push(cols[j].innerText);
			csv.push(row.join(","));        
		}
		downloadCSV(csv.join("\n"), filename);
	}
</script>

