<?php date_default_timezone_set("Asia/Manila"); ?>
<body style="padding:15px;font-family: Helvetica;">
<span id="amazing">
<style>
	body {padding:15px;	font-family: Helvetica;}
	#vhis26 tr th {font-family: Helvetica;text-align:center;vertical-align: middle;text-decoration:underline;font-weight:bold;color:#000;font-size:12px;border: 1px solid black;}
	#vhis26 tr td {font-family: Helvetica;color:#000;font-size:10px;border: 1px solid black;text-align:center;vertical-align: middle;}
	#rcode {font-family: Helvetica;font-weight:bolder;font-size:11px;text-align:left;float:left;}
	#rdate {font-family: Helvetica;font-weight:bolder;font-size:11px;text-align:right;float:right;}
	#rtitle {font-family: Helvetica;font-size:12px;font-weight:bolder;text-align:center;margin:0 0 30px;}
	#rtitle p {font-size:24px;font-weight:bold;text-align:center;margin:20px 0 30px;
	#dataResult { text-align:center; border: 1px solid black;}
	#dataSummary { text-align:center;background-color:#eee;font-size:14px !important;font-weight:bold; border: 1px solid black;}
	#footer { padding:20px 0; }
	#printer { font-size:13px; }
	
</style>
	<table class="table table-condensed" width="100%">
	<tr align="Left"><th colspan="8" style="text-align:left;font-family: Helvetica;font-size: 11px">Report Code: Vhis 26</th></tr>
	<tr align="Center"><th colspan="8" style="text-align:center"><span style="font-family: Helvetica;font-size: 22px">Non-Operating Concerns</span></th></tr>
	<tr align="Right"><th colspan="8" style="text-align:right;font-family: Helvetica;font-size: 11px">Report Interval: <?php echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y H:i:s",strtotime($dt)) ?></th></tr>
	</table>
		<table id="vhis26" class="table table-bordered table-condensed" width="100%" style="border: 1px solid black;">
			<tr style="border: 1px solid d7d7d7;background-color:#cacaca;"><th colspan="4">Concern Type</th><th>SOCIAL MEDIA</th><th>EMAIL</th><th>VOICE</th><th>Other</th></tr>
			<?php $socialplus = 0;$emailplus= 0;$voiceplus= 0;$otherplus=0;			?>
			<?php foreach($result as $data):
			$social = ($data->social_media == "" ? 0:$data->social_media);$email= ($data->emailx == "" ? 0:$data->emailx);$voice= ($data->voice == "" ? 0:$data->voice);$other= ($data->other == "" ? 0:$data->other);
			$socialplus += $social;$emailplus += $email;$voiceplus += $voice;$otherplus += $other;
			?><tr id="dataResult" style="border: 1px solid black;"><td align='left' colspan="4"><?php echo $data->concern; ?></td><td><?php echo $social; ?></td><td><?php echo $email; ?></td><td><?php echo $voice; ?></td><td><?php echo $other;  ?></td></tr>
			<?php endforeach; ?>
			<tr id="dataSummary" style="border: 1px solid black;"><th colspan="4">Total</th><th><?php echo $socialplus; ?></th><th><?php echo $emailplus; ?></th><th><?php echo $voiceplus; ?></th><th><?php echo $otherplus; ?></th></tr>
		</table>
	</div><br/>
		<table style="display:none;color:#fff;"><tr><th></th></tr><tr><th></th></tr></table><table style="display:none;color:#fff;">
		<tr><td><?php echo "Printed By: " . ($this->input->post('fullname') != "" ? $this->input->post('fullname'):$this->session->userdata('Fullname'))  . ": " .date('m/d/Y H:i:s'); ?></td></tr></table>
	<?php echo "<span style='font-family:Helvetica;font-size:11px;'>Printed By: " . ($this->input->post('fullname') != "" ? $this->input->post('fullname'):$this->session->userdata('Fullname')) . ": " .date('m/d/Y H:i:s') . '</span>'; ?>
</span>
	<div class="content" style="<?php  echo ($this->input->post("format") <> "" ? "display: none;" : "" ); ?>"><h3>Export:</h3>
		<div style="float:left; padding-right:10px;">
			<input type="button" class="btn btn-success" onclick="exportPDF();" value="PDF"> <input type="button" class="btn btn-success" onclick="exportXLS()" value="EXCEL"> <input type="button" class="btn btn-success" onclick="exportCSV()" value="CSV"> <input type="button" class="btn btn-success" onclick="exportDOC();" value="WORD"> <input type="button" class="btn btn-success" onclick="exportHTML();" value="HTML">
			<form id="exportPDF" action="<?php echo base_url("index.php/report_controller_z/generatePDF"); ?>" method="post" target="_blank"> <textarea id="pdfdata" name="html" style="display:none"></textarea> <input type="hidden" name="orient" value="1"> </form>
			<form id="exportXLS" action="<?php echo base_url("index.php/report_controller_z/excel"); ?>" method="post" target="_blank"> <textarea id="exceldata" name="xhtml" style="display:none"></textarea> <textarea id="excelcss" name="css" style="display:none"></textarea> </form>
			<form id="exportDOC" action="../report_controller_z/generateDOC"" method="post" target="_blank"> <textarea id="docdata" name="html" style="display:none"></textarea> <textarea id="htmlcss" name="css" style="display:none">gg</textarea> <input type="hidden" name="orient" value="1"> </form>
			<form id="exportHTML" action="../report_controller_z/generateHTML" method="post" target="_blank"> <textarea id="htmldata" name="html" style="display:none"></textarea> </form>
		</div>
			<form id="exportXLSServer" action="<?php echo base_url("index.php/report_controller_z/generateXLSServer"); ?>" method="post"> <textarea id="exceldataserver" name="html" style="display:none"></textarea> <textarea id="excelcssserver" name="css" style="display:none"></textarea> </form>
			<form id="exportPDFServer" action="<?php echo base_url("index.php/report_controller_z/generatePDFServer"); ?>" method="post"> <textarea id="pdfdataserver" name="html" style="display:none"></textarea> </form>
			<form id="exportDOCServer" action="<?php echo base_url("index.php/report_controller_z/generateDOCServer"); ?>" method="post"> <textarea id="docdataserver" name="html" style="display:none"></textarea> <textarea id="htmlcssserver" name="css" style="display:none"></textarea> </form>
			<form id="exportHTMLServer" action="<?php echo base_url("index.php/report_controller_z/generateHTMLServer"); ?>" method="post"> <textarea id="htmldataserver" name="html" style="display:none"></textarea> </form>
			<form id="exportCSVServer" action="<?php echo base_url("index.php/report_controller_z/generateCSVServer"); ?>" method="post"> <textarea id="csvdataserver" name="html" style="display:none"></textarea> </form>
		<div id="lol" style="display:none;"></div>
	</div>	<form id="exportCSVServer" action="<?php echo base_url("index.php/report_controller_z/generateCSVServer"); ?>" method="post"><textarea id="csvdataserver" name="html" style="display:none"></textarea></form>
	<div id="lol" style="display:none;"></div>
	<div id="css" style="display:none;">
	body {padding:15px;	font-family: Helvetica;}
	#vhis26 tr th {font-family: Helvetica;text-align:center;vertical-align: middle;text-decoration:underline;font-weight:bold;color:#000;font-size:12px;border: 1px solid black;}
	#vhis26 tr td {font-family: Helvetica;color:#000;font-size:10px;border: 1px solid black;}
	#rcode {font-family: Helvetica;font-weight:bolder;font-size:11px;text-align:left;float:left;}
	#rdate {font-family: Helvetica;font-weight:bolder;font-size:11px;text-align:right;float:right;}
	#rtitle {font-family: Helvetica;font-size:12px;font-weight:bolder;text-align:center;margin:0 0 30px;}
	#rtitle p {font-size:24px;font-weight:bold;text-align:center;margin:20px 0 30px;
	#dataResult { text-align:center; border: 1px solid black;}
	#dataSummary { text-align:center;background-color:#eee;font-size:14px !important;font-weight:bold; border: 1px solid black;}
	#footer { padding:20px 0; }
	#printer { font-size:13px; }</div>
<script>
	$(document).ready( function () {
		<?php if($this->input->post('scheduled') == true): ?>
		<?php if($this->input->post('format') == "PDF"): ?>
			$("#lol").text($("#amazing").html());
			$("#pdfdataserver").text($("#lol").text());
			setTimeout(function() {	$("#exportPDFServer").submit();},500);
		<?php elseif($this->input->post('format') == "WORD"): ?>
			$("#lol").text($("#amazing").html());
			$("#docdataserver").text($("#lol").text());
			$("#htmlcssserver").text($("#css").text());
			setTimeout(function() {$("#exportDOCServer").submit();},500);
		<?php elseif($this->input->post('format') == "HTML"): ?>
			$("#lol").text($("#amazing").html());
			$("#htmldataserver").text($("#lol").text());
			setTimeout(function() {$("#exportHTMLServer").submit();},500);
		<?php elseif($this->input->post('format') == "CSV"): ?>
			var csv = [];
			var rows = document.querySelectorAll("table tr");
			for (var i = 0; i < rows.length; i++) {var row = [], cols = rows[i].querySelectorAll("td, th");	for (var j = 0; j < cols.length; j++) row.push(cols[j].innerText);csv.push(row.join(","));}
			var finaldata = csv.join("\n");
			$("#csvdataserver").text(finaldata);
			setTimeout(function() {$("#exportCSVServer").submit();},500);
		<?php else: ?>
			$("#lol").text($("#amazing").html());
			$("#exceldataserver").text($("#lol").text());
			$("#excelcssserver").text($("#css").text());
			setTimeout(function() {$("#exportXLSServer").submit();},500);
		<?php endif; ?>
		<?php endif; ?>
		});
	function exportXLS() {
		$("#lol").text($("#amazing").html());$("#exceldata").text($("#lol").text());
		$("#excelcss").text($("#css").text());setTimeout(function() {$("#exportXLS").submit();},500);
	}
	function exportPDF() {
		$("#lol").text($("#amazing").html());$("#pdfdata").text($("#lol").text());
		setTimeout(function() {$("#exportPDF").submit();},500);
	}
	function exportDOC() {
		$("#lol").text($("#amazing").html());$("#docdata").text($("#lol").text());$("#htmlcss").text($("#css").text());
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
		var csvFile;
		var downloadLink;
		csvFile = new Blob([csv], {type: "text/csv"});
		downloadLink = document.createElement("a");
		downloadLink.download = filename;
		downloadLink.href = window.URL.createObjectURL(csvFile);
		downloadLink.style.display = "none";
		document.body.appendChild(downloadLink);
		downloadLink.click();
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
			//row.push(cols[j].innerText);
			csv.push(row.join(","));        
		}
		
		downloadCSV(csv.join("\n"), filename);
	}
</script></body></html>