<?php 
	ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
	date_default_timezone_set("Asia/Manila");
	function secToHR($seconds) { $hours = floor($seconds / 3600); $minutes = floor(($seconds / 60) % 60); $seconds = floor($seconds % 60);
	return str_pad($hours, 2, '0', STR_PAD_LEFT).":".str_pad($minutes, 2, '0', STR_PAD_LEFT).":".str_pad($seconds, 2, '0', STR_PAD_LEFT);
}
	$sitearray = []; $servicearray = []; $skillarray = []; $dataarray = [];
	foreach($z1->result() as $rdata){
			switch($range){
				case "Fifteen":
				$Intervaldate = date("m/d/Y H:i:s",strtotime($rdata->{'15MinsInterval'})) ." - ".date(" H:i:s",strtotime($rdata->{'15MinsInterval'} . " + 15 Minutes")) ;
				break;
				case "Thirty":
				$Intervaldate =  date("m/d/Y H:i:s",strtotime($rdata->{'30MinsInterval'})) ." - ".date(" H:i:s",strtotime($rdata->{'30MinsInterval'} . " + 30 Minutes")) ;
				break;
				case "Sixty":
				$Intervaldate =  date("m/d/Y H:i:s",strtotime($rdata->{'60MinsInterval'})) ." - ".date(" H:i:s",strtotime($rdata->{'60MinsInterval'} . " + 60 Minutes")) ;
				break;
				case "D":
				$Intervaldate = $rdata->perDate;
				break;
				case "W":
				$Intervaldate = $rdata->Sunday . " - ".$rdata->Saturday;
				break;
				case "PM":
				case "M":
				$Intervaldate = $rdata->MONTH_NAME;
				break;
				case "Y":
				$Intervaldate = $rdata->YEAR;
				break;
				case "Morning":
				$Intervaldate = $rdata->Morning;
				break;
				case "Afternoon":
				$Intervaldate = $rdata->Afternoon;
				break;
				case "Graveyard":
				$Intervaldate = $rdata->Graveyard;
				break;
			}
			if($Intervaldate != ""){
				$repdata[$rdata->SiteName][$rdata->SiteName ."|".$rdata->Service_c."|".$rdata->Skill_Desc][$Intervaldate][] = $rdata;
				$rdata2[$rdata->SiteName][] = $rdata;
				if(!in_array($rdata->SiteName,$sitearray)){
					array_push($sitearray, $rdata->SiteName);
				}
				if(!in_array($rdata->Service_c,$servicearray)){
					array_push($servicearray, $rdata->Service_c);
				}
				if(!in_array($rdata->Skill_Desc,$skillarray)){
					array_push($skillarray, $rdata->Skill_Desc);
				}
				if(!in_array($rdata->SiteName ."|".$rdata->Service_c."|".$rdata->Skill_Desc,$dataarray)){
					array_push($dataarray, $rdata->SiteName ."|".$rdata->Service_c."|".$rdata->Skill_Desc);
				}
			}
	}
	asort($sitearray); asort($servicearray); asort($skillarray); asort($dataarray);
?>
<body style="padding:15px;font-family: Arial;">
<span id="amazing">
<style type="text/css">
	#vhis28 { border:1px solid #eee; }
	#vhis28 tr th { font-family: Arial; text-align:center; vertical-align: middle; text-decoration:underline; border:1px solid #eee; font-weight:bold; color:#000; font-size:9px; padding:5px; }
	#vhis28 tr td { font-family: Arial; color:#000; border:1px solid #eee; font-size:10px; padding:5px;text-align:center; }
	#rcode { font-family: Arial; font-weight:bolder; font-size:11px; text-align:left; float:left; }
	#rdate {font-family: Arial;font-weight:bolder; font-size:11px; text-align:right; float:right; }
	#rtitle { font-family: Arial; font-size:12px; font-weight:bolder; text-align:center; margin:0 0 30px; }
	#rtitle p { font-family: Arial; font-size:24px; font-weight:bold; text-align:center; margin:20px 0 30px; }
	#dataResult { font-family: Arial;text-align:center; }
	#dataSummary { font-family: Arial;text-align:center;background-color:#eee;font-size:14px !important;font-weight:bold; }
	#footer { font-family: Arial;padding:20px 0; }
	#printer { font-family: Arial;font-size:13px; }
</style>
	<div class="responsive" style="padding:15px;">
	<table class="table table-condensed" style="width:100%">
		<tr align="Center"><th colspan="18" style="text-align:left;font-family: Helvetica;font-size: 11px">Report code: vhis 33</th></tr>
		<tr align="Center"><th colspan="18" ><span style="text-align:center;font-family: Helvetica;font-size: 22px">Summary</span></th></tr>
		<tr  align="Right"><th colspan="18" style="text-align:right;font-family: Helvetica;font-size: 11px">Report Interval: <?php 
	if($range=="Morning"){
	echo date("m/d/Y 06:00:00 ",strtotime($df))." - ". date("m/d/Y 14:00:00",strtotime($dt));
	}elseif($range=="Afternoon"){
	echo date("m/d/Y 14:00:00 ",strtotime($df))." - ". date("m/d/Y 22:00:00",strtotime($dt));
	}elseif($range=="Graveyard"){
	echo date("m/d/Y 22:00:00 ",strtotime($df))." - ". date("m/d/Y 06:00:00",strtotime($dt)); 
	}elseif($range=="Fifteen"){
		if(date("H:i",strtotime($dt))!='23:59'){ echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y H:i:s",strtotime($dt));  }else{ echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y 23:45:00",strtotime($dt));  }
	}elseif($range=="Thirty"){
		if(date("H:i",strtotime($dt))!='23:59'){ echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y H:i:s",strtotime($dt));  }else{ echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y 23:30:00",strtotime($dt));  }
	}elseif($range=="Sixty"){
		if(date("H:i",strtotime($dt))!='23:59'){ echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y H:i:s",strtotime($dt));  }else{ echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y 23:00:00",strtotime($dt));  }
	}elseif($range=="W"){
		$day1 = date('w',strtotime($df));
		$day2 = date('w',strtotime($dt));
		echo date('m/d/Y H:i:s ', strtotime($df .' -'.$day1.' days'))." - ". date("m/d/Y 23:59:59" ,strtotime($dt .' +'.(6-$day2).' days'));
	}else{
		echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y H:i:s",strtotime($dt)); 
	}
	?></th></tr>
</table>		
		<div class="row"> <div id="rtitle" class="col-12"><span style="font-family:Helvetica;font-size:12px;">SkillSet Interval Summary</span></div> </div>
		<table cellpadding="0" cellspacing="0" id="vhis28" class="table table-bordered table-condensed" width="100%">
		<tr>
			<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'></th>
			<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>Calls Offered</th>
			<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>Calls Answered</th>
			<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>Ans w/in Threshold</th>
			<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>% Answer Level</th>
			<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>% Service Level</th>
			<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>ASA Group</th>
			<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>AHT Group</th>
			<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>Online (Novice)</th>
			<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>ASA (Novice)</th>
			<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>AHT Novice</th>
			<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>Online (Developing Expert)</th>
			<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>ASA (Developing Expert)</th>
			<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>AHT Developing Expert</th>
			<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>Online (Expert)</th>
			<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>ASA (Expert)</th>
			<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>AHT Expert</th>
			<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>Total Inbound</th>
		</tr>
		<!-- GRAND TOTAL -->
		<?php 
				$tt_off =0; $tt_ans =0; $tt_ansth =0; $tt_anslvl =0; $tt_svclvl =0; $tt_asagrp =0; $tt_ahtgrp =0; $tt_onlnvc =0; $tt_asanvc =0; $tt_ahtnvc =0; $tt_onldevexp =0; $tt_asadevexp =0; $tt_ahtdevexp =0; 
				$tt_onexp =0; $tt_asaexp =0; $tt_ahtexp =0; $tt_totinb =0; $asa = 0; $tt_ahtgrpgt = 0; $tt_ahtnvcgt = 0; $tt_ahtdevexpgt = 0; $tt_ahtexpgt = 0; $sumssss = 0;
				foreach($sitearray as $siteIN){
					if(isset($repdata[$siteIN])){
						$xsite = ""; $xservice =""; $xskill ="";
						foreach($dataarray as $dataIN){
							if(isset($repdata[$siteIN][$dataIN])){
							foreach($repdata[$siteIN][$dataIN] as $rkey => $rval){
									$o = 0; $a = 0; $awt = 0; $al = 0; $sl = 0; $asag = 0; $asagx = 0; $ahtg = 0; $ahtgb = 0;
									$on = 0; $asan = 0; $asanx = 0; $ahtn = 0; $ahtng = 0; $od = 0; $asad = 0; $asadx = 0; $ahtd = 0; $ahtdg = 0; $oe = 0; $asae = 0; $asaex = 0; $ahte = 0; $ahteg = 0; $ti = 0; $asa = 0;
									$agents=[];
									foreach($rval as $key => $val){
										
									$o += $val->Offered; $a += $val->Answered;
									$awt += $val->WithinThreshold; $asag += $val->ASA_Group;
									$ahtg += $val->AHT_Group; $ahtgb += ($val->AHT_Group * $val->Answered);
									$on += $val->NoviceAnswered; $asan += $val->ASA_Novice;
									$ahtn += $val->AHT_Novice; $ahtng += $val->AHT_Novice * $val->NoviceAnswered;
									$od += $val->DevExpertAnswered; $asad += $val->ASA_DevExpert; $ahtd += $val->AHT_DevExpert;
									$ahtdg += $val->AHT_DevExpert *$val->DevExpertAnswered;
									$oe += $val->ExpertAnswered; $asae += $val->ASA_Expert; $ahte += $val->AHT_Expert;
									$ahteg += $val->AHT_Expert * $val->ExpertAnswered; $ti += $val->Answered;
									array_push($agents,$val->AnsUser_Id);
									}
									
									$tt_off= $tt_off + $o; $tt_ans = $tt_ans + $a; $tt_ansth = $tt_ansth + $awt; $tt_anslvl = $tt_anslvl + $al; $tt_svclvl = $tt_svclvl + $sl;
									$tt_ahtgrp = $tt_ahtgrp + $ahtgb; $tt_ahtgrpgt = $tt_ahtgrpgt + $ahtgb;
									$tt_onlnvc = $tt_onlnvc + $on; $tt_ahtnvc = $tt_ahtnvc + $ahtng; $tt_ahtnvcgt = $tt_ahtnvcgt + ($tt_onlnvc * $tt_ahtnvc);
									$tt_onldevexp = $tt_onldevexp + $od; $tt_ahtdevexp = $tt_ahtdevexp + $ahtdg; $tt_ahtdevexpgt = $tt_ahtdevexpgt + ($tt_onldevexp * $tt_ahtdevexp);
									$tt_onexp = $tt_onexp + $oe; $tt_ahtexp = $tt_ahtexp + $ahteg; $tt_ahtexpgt = $tt_ahtexpgt + ($tt_onexp * $tt_ahtexp);
									$tt_totinb =$tt_onexp +$tt_onlnvc + $tt_onldevexp;
									
									if($o != 0){ $al = ($a/$o) * 100 ; $sl = ($awt/$o) * 100 ; }
									if($a != 0){ $asagx = ($asag/$a); }
									if($on != 0){ $asanx = ($asan/$on); }
									if($od != 0){ $asadx = ($asad/$od); }
									if($oe != 0){$asaex = ($asae/$oe);}
									
									$tt_asagrp = $tt_asagrp + $asag;
									$tt_asanvc = $tt_asanvc + $asan;
									$tt_asadevexp = $tt_asadevexp + $asad;
									$tt_asaexp = $tt_asaexp + $asae;									
									}
								}
							}
						}
				}			
			echo "<tr>
				<td style='text-align:center;font-family: Helvetica;font-size: 11px; font-weight: bold;background-color:#e2e2e2'> Grand Total: </td>
				<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2'> ".$tt_off."</td>
				<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2'> ".$tt_ans ."</td>
				<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2'> ".$tt_ansth ."</td>
				<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2'> ";if($tt_ans == "0"){ echo "0"; }else {echo number_format(($tt_ans / $tt_off) * 100,2); } echo "%</td>
				<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2'> "; if($tt_ansth == "0"){ echo "0"; }else {echo number_format(($tt_ansth / $tt_off) * 100,2);} echo "%</td>
				<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2'> "; if($tt_asagrp == "0"){echo $ctr->secToHR(0);} else{ echo $ctr->secToHR(round($tt_asagrp /$tt_ans),2);} echo "</td>
				<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2'> "; if($tt_ahtgrpgt == "0"){echo $ctr->secToHR(0);}else{ echo $ctr->secToHR(round($tt_ahtgrpgt/$tt_ans),2); } echo "</td>
				<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2'> ".$tt_onlnvc."</td>
				<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2'> "; if($tt_asanvc == "0"){ echo $ctr->secToHR(0); }else{ echo $ctr->secToHR(round($tt_asanvc /$tt_onlnvc),2);} echo "</td>
				<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2'>  "; if($tt_ahtnvc == "0"){ echo $ctr->secToHR(0); }else{ echo $ctr->secToHR(round($tt_ahtnvc/$tt_onlnvc),2);} echo "</td>
				<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2'> ".$tt_onldevexp." </td>
				<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2'> "; if($tt_asadevexp == "0"){ echo $ctr->secToHR(0); }else{ echo $ctr->secToHR(round($tt_asadevexp /$tt_onldevexp),2);} echo"</td>
				<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2'>  "; if($tt_ahtdevexp == "0"){ echo $ctr->secToHR(0); }else{ echo $ctr->secToHR(round($tt_ahtdevexp/$tt_onldevexp),2);} echo "</td>
				<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2'> ".$tt_onexp."</td>
				<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2'>  "; if($tt_asaexp == "0"){ echo $ctr->secToHR(0); }else{ echo $ctr->secToHR(round($tt_asaexp/$tt_onexp),2);} echo "</td>
				<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2'>  "; if($tt_ahtexp == "0"){ echo $ctr->secToHR(0); }else{ echo $ctr->secToHR(round($tt_ahtexp/$tt_onexp),2);} echo "</td>
				<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2'> ". $tt_totinb." </td></tr>";
			?>
			<?php 
				foreach($sitearray as $siteIN){
					if(isset($repdata[$siteIN])){
						$xsite = "";
						$xservice ="";
						$xskill ="";
						foreach($dataarray as $dataIN){
							if(isset($repdata[$siteIN][$dataIN])){
								$headers = explode("|",$dataIN);
							if (str_replace(" " ,"",$headers[1]) != ""){
								if($xsite != $headers[0]){
								$xsite = $headers[0];
								$site_o = 0; $site_a = 0; $site_awt = 0; $site_al = 0; $site_sl = 0; $site_asag = 0; $site_asagx = 0; $site_ahtg = 0; $site_on = 0; $site_asan = 0;
								$site_asanx = 0; $site_ahtn = 0; $site_ahtng = 0; $site_od = 0; $site_asad = 0; $site_asadx = 0; $site_ahtd = 0; $site_ahtdg = 0; $site_oe = 0; $site_asae = 0;
								$site_asaex = 0; $site_ahte = 0; $site_ahteg = 0; $site_ti = 0; $site_asa = 0; $ahtnxx = 0; $ahtdxx = 0; $ahtexx = 0;
								$x_agents=[]; $ahtgb= 0;
								foreach($rdata2[$xsite] as $row => $value){
								$site_o += $value->Offered; $site_a += $value->Answered; $site_awt += $value->WithinThreshold; $site_asag += $value->ASA_Group;
								$site_ahtg += $value->AHT_Group; $ahtgb += ($value->AHT_Group * $value->Answered); $site_on += $value->NoviceAnswered; $site_asan += $value->ASA_Novice; $site_ahtn += $value->AHT_Novice;
								$site_ahtng += $value->AHT_Novice * $value->NoviceAnswered; $site_od += $value->DevExpertAnswered; $site_asad += $value->ASA_DevExpert; $site_ahtd += $value->AHT_DevExpert;
								$site_ahtdg += $value->AHT_DevExpert * $value->DevExpertAnswered; $site_oe += $value->ExpertAnswered; $site_asae += $value->ASA_Expert;
								$site_ahte += $value->AHT_Expert; $site_ahteg += $value->AHT_Expert * $value->ExpertAnswered; $site_ti += $value->Answered;
								array_push($x_agents,$value->AnsUser_Id);
								}
								if($site_o != 0){$site_al = ($site_a/$site_o) * 100 ;$site_sl = ($site_awt/$site_o) * 100 ;}
								if($site_a != 0){ $site_asagx = ($site_asag/$site_a); $site_ahtgg = $ahtgb/$site_a; }
								if($site_on != 0){ $site_asanx = ($site_asan/$site_on); $ahtnxx = ($site_ahtng/$site_on); }
								if($site_od != 0){ $site_asadx = ($site_asad/$site_od); $ahtdxx = ($site_ahtdg/$site_od);}
								if($site_oe != 0){ $site_asaex = ($site_asae/$site_oe);
									$ahtexx = ($site_ahteg/$site_oe);}
								echo "<tr style='border none !important;'><th colspan='18' style='border: none !important;'></th></tr><tr  style='text-align:left;font-weight: bold;border: 1px solid #d7d7d7 ;background-color:#36CCF8'><th>Site Name: ".$headers[0]."</th>";
								echo"
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>$site_o</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>$site_a</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>$site_awt</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".number_format($site_al,2)."%</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".number_format($site_sl,2)."%</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$ctr->secToHR(round($site_asagx))."</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'> ".$ctr->secToHR(round($site_ahtgg))."</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>$site_on</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$ctr->secToHR(round($site_asanx))."</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$ctr->secToHR(round($ahtnxx))."</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>$site_od</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$ctr->secToHR(round($site_asadx))."</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$ctr->secToHR(round($ahtdxx))."</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>$site_oe</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$ctr->secToHR(round($site_asaex))."</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$ctr->secToHR(round($ahtexx))."</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".($site_oe+ $site_on+ $site_od)."</td></tr>
									";
								/*** END OF SITE SUMMARY ***/
								}
								if($xservice != $headers[1]){
								$xservice = $headers[1];
								$service_o = 0; $service_a = 0; $service_awt = 0; $service_al = 0; $service_sl = 0; $service_asag = 0; $service_asagx = 0; $service_ahtg = 0;
								$service_on = 0; $service_asan = 0; $service_asanx = 0; $service_ahtn = 0; $service_ahtng = 0; $service_od = 0; $service_asad = 0;
								$service_asadx = 0; $service_ahtd = 0; $service_ahtdg = 0; $service_oe = 0; $service_asae = 0; $service_asaex = 0; $service_ahte = 0; $service_ahteg = 0; $service_ti = 0;
								$service_asa = 0; $serv_ahtnxx = 0; $serv_ahtdxx = 0; $serv_ahtexx = 0; $serv_ahtgb= 0; $service_ahtgg = 0;
								foreach($rdata2[$xsite] as $row => $value){
									if ($value->Service_c == $xservice){
										$service_o += $value->Offered; $service_a += $value->Answered; $service_awt += $value->WithinThreshold; $service_asag += $value->ASA_Group;
										$service_ahtg += $value->AHT_Group; $ahtgb += ($value->AHT_Group * $value->Answered); $service_on += $value->NoviceAnswered; $service_asan += $value->ASA_Novice;
										$service_ahtn += $value->AHT_Novice; $service_ahtng += $value->AHT_Novice * $value->NoviceAnswered; $service_od += $value->DevExpertAnswered; $service_asad += $value->ASA_DevExpert;
										$service_ahtd += $value->AHT_DevExpert; $service_ahtdg += $value->AHT_DevExpert * $value->DevExpertAnswered; $service_oe += $value->ExpertAnswered; $service_asae += $value->ASA_Expert; $service_ahte += $value->AHT_Expert; $service_ahteg += $value->AHT_Expert * $value->ExpertAnswered; $service_ti += $value->Answered;
									}
								}
								if($service_o != 0){$service_al = ($service_a/$service_o) * 100 ;$service_sl = ($service_awt/$service_o) * 100 ;}
								if($service_a != 0){ $service_asagx = ($service_asag/$service_a); $service_ahtgg = $serv_ahtgb/$service_a; }
								if($service_on != 0){ $service_asanx = ($service_asan/$service_on); $serv_ahtnxx = ($service_ahtng/$service_on); }
								if($service_od != 0){ $service_asadx = ($service_asad/$service_od); $serv_ahtdxx = ($service_ahtdg/$service_od);}
								if($service_oe != 0){ $service_asaex = ($service_asae/$service_oe); $serv_ahtexx = ($service_ahteg/$service_oe);}
								echo "<tr><th style='font-weight: bold;text-align:left;font-family: Helvetica;font-size: 11px;background-color:#B7EDFD;'>Application: ".$headers[1]."</th>";
								echo"
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#B7EDFD;'><b>$service_o</b></td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#B7EDFD;'><b>$service_a</b></td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#B7EDFD;'><b>$service_awt</b></td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#B7EDFD;'><b>".number_format($service_al,2)."%</b></td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#B7EDFD;'><b>".number_format($service_sl,2)."%</b></td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#B7EDFD;'><b>".$ctr->secToHR(round($service_asagx))."</b></td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#B7EDFD;'><b> ".$ctr->secToHR(round($service_ahtgg))."</b></td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#B7EDFD;'><b>$service_on</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#B7EDFD;'><b>".$ctr->secToHR(round($service_asanx))."</b></td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#B7EDFD;'><b>".$ctr->secToHR(round($serv_ahtnxx))."</b></td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#B7EDFD;'><b>$service_od</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#B7EDFD;'><b>".$ctr->secToHR(round($service_asadx))."</b></td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#B7EDFD;'><b>".$ctr->secToHR(round($serv_ahtdxx))."</b></td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#B7EDFD;'><b>$service_oe</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#B7EDFD;'><b>".$ctr->secToHR(round($service_asaex))."</b></td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#B7EDFD;'><b>".$ctr->secToHR(round($serv_ahtexx))."</b></td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#B7EDFD;'><b>".($service_oe+ $service_on+ $service_od)."</b></td></tr>
									"; 
								}
								echo "<tr><th colspan='18' style='font-weight: bold;text-align:left;font-family: Helvetica;font-size: 11px;background-color:#cacaca;'>Skillset: ".$headers[2]."</th></tr>";
							?>
								<tr>
									<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>Interval</th>
									<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>Calls Offered</th>
									<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>Calls Answered</th>
									<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>Ans w/in Threshold</th>
									<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>% Answer Level</th>
									<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>% Service Level</th>
									<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>ASA Group</th>
									<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>AHT Group</th>
									<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>Online (Novice)</th>
									<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>ASA (Novice)</th>
									<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>AHT Novice</th>
									<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>Online (Developing Expert)</th>
									<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>ASA (Developing Expert)</th>
									<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>AHT Developing Expert</th>
									<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>Online (Expert)</th>
									<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>ASA (Expert)</th>
									<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>AHT Expert</th>
									<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;'>Total Inbound</th>
								</tr>
							<?php
							$xskill = $headers[2];
							$tt_off =0;$tt_ans =0; $tt_ansth =0; $tt_anslvl =0; $tt_svclvl =0; $tt_asagrp =0; $tt_ahtgrp =0; $tt_onlnvc =0; $tt_asanvc =0; $tt_ahtnvc =0; $tt_onldevexp =0; 
							$tt_asadevexp =0; $tt_ahtdevexp =0; $tt_onexp =0; $tt_asaexp =0; $tt_ahtexp =0; $tt_totinb =0; $asa = 0;
							foreach($repdata[$siteIN][$dataIN] as $rkey => $rval){
								echo "<tr><td style='text-align:center;font-family: Helvetica;font-size: 11px;'>$rkey</td>";
									$o = 0; $a = 0; $awt = 0; $al = 0; $sl = 0; $asag = 0; $asagx = 0; $ahtg = 0; $ahtgb = 0; $on = 0; $asan = 0; $asanx = 0; $ahtn = 0; $ahtng = 0; $ahtngx = 0; $od = 0; $asad = 0; $asadx = 0;
									$ahtd = 0; $ahtdg = 0; $ahtdgx = 0; $oe = 0; $asae = 0; $asaex = 0; $ahte = 0; $ahteg = 0; $ahtegx = 0; $ti = 0; $asa = 0; $ahtgg = 0;
									foreach($rval as $key => $val){
										$o += $val->Offered; $a += $val->Answered; $awt += $val->WithinThreshold; $asag += $val->ASA_Group; $ahtg += $val->AHT_Group; $on += $val->NoviceAnswered; $asan += $val->ASA_Novice;
										$ahtn += $val->AHT_Novice; $ahtng += $val->AHT_Novice * $val->NoviceAnswered; $od += $val->DevExpertAnswered; $asad += $val->ASA_DevExpert;
										$ahtd += $val->AHT_DevExpert; $ahtdg += $val->AHT_DevExpert * $val->DevExpertAnswered; $oe += $val->ExpertAnswered; $asae += $val->ASA_Expert;
										$ahte += $val->AHT_Expert; $ahteg += $val->AHT_Expert * $val->ExpertAnswered; $ti += $val->Answered; $ahtgb += ($val->AHT_Group * $val->Answered);
										array_push($agents,$val->AnsUser_Id);
									}
									$tt_off = $tt_off + $o; $tt_ans = $tt_ans + $a; $tt_ansth = $tt_ansth + $awt; $tt_anslvl = $tt_anslvl + $al; $tt_svclvl = $tt_svclvl + $sl; $tt_ahtgrp = $tt_ahtgrp + $ahtgb; $tt_onlnvc = $tt_onlnvc + $on;
									$tt_ahtnvc = $tt_ahtnvc + $ahtng; $tt_onldevexp = $tt_onldevexp + $od; $tt_ahtdevexp = $tt_ahtdevexp + $ahtdg; $tt_onexp = $tt_onexp + $oe; $tt_ahtexp = $tt_ahtexp + $ahteg; $tt_totinb =$tt_onexp +$tt_onlnvc + $tt_onldevexp;
									if($o != 0){ $al = ($a/$o) * 100 ; $sl = ($awt/$o) * 100 ; }
									if($a != 0){ $asagx = ($asag/$a); $ahtgg = ($ahtgb/$a); }
									if($on != 0){ $asanx = ($asan/$on); $ahtngx = ($ahtng/$on); }
									if($od != 0){ $asadx = ($asad/$od); $ahtdgx = ($ahtdg/$od); }
									if($oe != 0){ $asaex = ($asae/$oe); $ahtegx = ($ahteg/$oe); }
									$tt_asagrp = $tt_asagrp + $asag; $tt_asanvc = $tt_asanvc + $asan; $tt_asadevexp = $tt_asadevexp + $asad; $tt_asaexp = $tt_asaexp + $asae;
									echo" <td style='text-align:center;font-family: Helvetica;font-size: 11px;'>$o</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>$a</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>$awt</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".number_format($al,2)."%</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".number_format($sl,2)."%</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$ctr->secToHR(round($asagx))."</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$ctr->secToHR(round($ahtgg))."</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>$on</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$ctr->secToHR(round($asanx))."</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$ctr->secToHR(round($ahtngx))."</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>$od</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$ctr->secToHR(round($asadx))."</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$ctr->secToHR(round($ahtdgx))."</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>$oe</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$ctr->secToHR(round($asaex))."</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$ctr->secToHR(round($ahtegx))."</td>
									<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>". ($on+ $od+ $oe)."</td></tr>";
								}
								echo "<tr style='border none !important; background-color:#e2e2e2;'>
										<td style='text-align:center;font-family: Helvetica;font-size: 11px; font-weight: bold;'> Summary </td>
										<td style='text-align:center;font-family: Helvetica;font-size: 11px;'> ".$tt_off."</td>
										<td style='text-align:center;font-family: Helvetica;font-size: 11px;'> ".$tt_ans ."</td>
										<td style='text-align:center;font-family: Helvetica;font-size: 11px;'> ".$tt_ansth ."</td>
										<td style='text-align:center;font-family: Helvetica;font-size: 11px;'> ";if($tt_ans == "0"){ echo "0"; }else {echo number_format(($tt_ans / $tt_off) * 100,2); } echo "%</td>
										<td style='text-align:center;font-family: Helvetica;font-size: 11px;'> "; if($tt_ansth == "0"){ echo "0"; }else {echo number_format(($tt_ansth / $tt_off) * 100,2);} echo "%</td>
										<td style='text-align:center;font-family: Helvetica;font-size: 11px;'> "; if($tt_asagrp == "0"){echo $ctr->secToHR(0);} else{ echo $ctr->secToHR(round($tt_asagrp /$tt_ans),2);} echo "</td>
										<td style='text-align:center;font-family: Helvetica;font-size: 11px;'> "; if($tt_ahtgrp == "0"){echo $ctr->secToHR(0);}else{ echo $ctr->secToHR(round($tt_ahtgrp/$tt_ans)); } echo "</td>
										<td style='text-align:center;font-family: Helvetica;font-size: 11px;'> ".$tt_onlnvc."</td>
										<td style='text-align:center;font-family: Helvetica;font-size: 11px;'> "; if($tt_asanvc == "0"){ echo $ctr->secToHR(0); }else{ echo $ctr->secToHR(round($tt_asanvc /$tt_onlnvc),2);} echo "</td>
										<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>  "; if($tt_ahtnvc == "0"){ echo $ctr->secToHR(0); }else{ echo $ctr->secToHR(round($tt_ahtnvc/$tt_onlnvc),2);} echo "</td>
										<td style='text-align:center;font-family: Helvetica;font-size: 11px;'> ".$tt_onldevexp." </td>
										<td style='text-align:center;font-family: Helvetica;font-size: 11px;'> "; if($tt_asadevexp == "0"){ echo $ctr->secToHR(0); }else{ echo $ctr->secToHR(round($tt_asadevexp /$tt_onldevexp),2);} echo"</td>
										<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>  "; if($tt_ahtdevexp == "0"){ echo $ctr->secToHR(0); }else{ echo $ctr->secToHR(round($tt_ahtdevexp/$tt_onldevexp),2);} echo "</td>
										<td style='text-align:center;font-family: Helvetica;font-size: 11px;'> ".$tt_onexp."</td>
										<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>  "; if($tt_asaexp == "0"){ echo $ctr->secToHR(0); }else{ echo $ctr->secToHR(round($tt_asaexp/$tt_onexp),2);} echo "</td>
										<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>  "; if($tt_ahtexp == "0"){ echo $ctr->secToHR(0); }else{ echo $ctr->secToHR(round($tt_ahtexp/$tt_onexp),2);} echo "</td>
										<td style='text-align:center;font-family: Helvetica;font-size: 11px;'> ". $tt_totinb." </td>
									</tr>";
								echo "<tr style='border none !important;'><td colspan='18' style='border: none !important;'></td></tr>";
							}
							}
						}
					}
				}
			?>
		</table><br/>
			<table width="100%"> <tr><th colspan="5" style="text-align:center;font-family:Helvetica;font-size:13px;">Total Calls Answered per Skillset Application</th></tr></table><br/>
		<?php
			$SiteIN=[];$IntervalIn=[]; $AppIn=[]; $SkillIn=[]; $count=[]; $NoviceAnswered=[]; $DevExpertAnswered=[]; $ExpertAnswered=[];
foreach($z1->result() as $row) {
	$interval="";
		if($freq=="Fifteen")
			{	
				$xdate = date('m/d/Y', strtotime($row->{'FifteenMinuteInterval'})); 
				$xtime = substr($row->{'15MinsInterval'}, -12);
				$xtime1 = date('H:i:s ',strtotime($xtime));
				$xtime2 = date('H:i:s',strtotime("+15 minutes", strtotime($xtime)));
				$xfinal= $xdate . ' ' .$xtime1 . ' - '.$xtime2; 
				$interval=$xfinal;
			}elseif($freq=="Thirty"){
				$xdate = date('m/d/Y', strtotime($row->{'FifteenMinuteInterval'})); 
				$xtime = substr($row->{'30MinsInterval'}, -12);
				$xtime1 = date('H:i:s ',strtotime($xtime));
				$xtime2 = date('H:i:s',strtotime("+30 minutes", strtotime($xtime)));
				$xfinal= $xdate . ' ' .$xtime1 . ' - '.$xtime2; 
				$interval=$xfinal;
			}elseif($freq=="Sixty"){
				$xdate = date('m/d/Y', strtotime($row->{'FifteenMinuteInterval'})); 
				$xtime = substr($row->{'60MinsInterval'}, -12);
				$xtime1 = date('H:i:s ',strtotime($xtime));
				$xtime2 = date('H:i:s',strtotime("+60 minutes", strtotime($xtime)));
				$xfinal= $xdate . ' ' .$xtime1 . ' - '.$xtime2; 
				$interval=$xfinal;
			}elseif($freq=="D"){
				$interval=$row->perDate;
			}elseif($freq=="W"){
				$interval=$row->{'Sunday'}.' - ' . $row->{'Saturday'};
			}elseif($freq=="M"){
				$interval=$row->{'MONTH_NAME'};
			}elseif($freq=="PM"){
				$interval=$row->{'MONTH_NAME'};
			}elseif($freq=="Y"){
				$interval=$row->YEAR;
			}elseif($freq=="Morning"){
				if($row->Morning ==''){ continue; } 
				$interval= $row->Morning;
			}elseif($freq=="Afternoon"){
				if($row->Afternoon ==''){ continue; }
				$interval= $row->Afternoon;
			}elseif($freq=="Graveyard"){
				if($row->Graveyard ==''){ continue; }
				$interval= $row->Graveyard;
			}
	if(!in_array($row->SiteName,$SiteIN)){
		array_push($SiteIN, $row->SiteName);
		$siteLoc = array_search($row->SiteName, $SiteIN);
		$AppIn[ $siteLoc ] = []; $SkillIn[ $siteLoc ] = []; $IntervalIn[ $siteLoc ] = [];				
	}else{
		$siteLoc = array_search($row->SiteName, $SiteIN);
	}
	if(!in_array($row->Service_c,$AppIn[$siteLoc])){ array_push($AppIn[$siteLoc], $row->Service_c); }
	asort($AppIn[$siteLoc]);
	if(!in_array($row->Skill_Desc,$SkillIn[$siteLoc])){ array_push($SkillIn[$siteLoc], $row->Skill_Desc); }	
	asort($SkillIn[$siteLoc]);
	if(!in_array($interval,$IntervalIn[$siteLoc])){ array_push($IntervalIn[$siteLoc], $interval);}
	if($freq=="M" || $freq=="M"){}else{
	asort($IntervalIn[$siteLoc]);};
	$appLoc = array_search($row->Service_c, $AppIn[$siteLoc]);
	$skiLoc = array_search($row->Skill_Desc, $SkillIn[$siteLoc]);
	$inLoc = array_search($interval, $IntervalIn[$siteLoc]);
	if(isset($count[$siteLoc][$appLoc][$skiLoc][$inLoc])){ $count[$siteLoc][$appLoc][$skiLoc][$inLoc]++; }else{ $count[$siteLoc][$appLoc][$skiLoc][$inLoc]=0; }
	if(isset($NoviceAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc])){
		if($row->NoviceAnswered=='' ){ }else{ $NoviceAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]++; }
	}else{
		$NoviceAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]=0;
		if($row->NoviceAnswered!='' ){ $NoviceAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]++; }
	}
	if(isset($DevExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc])){
		if($row->DevExpertAnswered==''){ }else{ $DevExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]++; }
	}else{
		$DevExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]=0;
		if($row->DevExpertAnswered!='' ){ $DevExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]++; }
	}
	if(isset($ExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc])){
		if($row->ExpertAnswered=='' ){ }else{ $ExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]++; }
	}else{
		$ExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]=0;
		if($row->ExpertAnswered!='' ){ $ExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]++; }
	}
}
	asort($SiteIN);
		echo "<table class='table table-condensed' style='font-family:Helvetica; font-size:11px;width:100%'>
			<tr>";
		echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;' width='20%'></th>";
		echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;' width='20%'>NOVICE</th>";
		echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;' width='20%'>DEVELOPING EXPERT</th>";
		echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;' width='20%'>EXPERT</th>";
		echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;' width='20%'>Total Answered</th>
		</tr>";
			$sumnov=0; $sumdev=0; $sumexp=0; $sumsum=0;
		foreach($SiteIN as $fsite){
			$loc = array_search($fsite, $SiteIN);
			foreach($AppIn[$loc] as $fservice){
					$appLoc = array_search($fservice, $AppIn[$loc]);
				$skillx = 0;
				foreach($SkillIn[$loc] as $fskill){
				$skiLoc = array_search($fskill, $SkillIn[$loc]);
						$tbrow="";
						foreach($IntervalIn[$loc] as $finterval){
						$nov=0; $dev=0; $exp=0; $sum=0;
						$inLoc = array_search($finterval, $IntervalIn[$loc]);							
						$nov=(isset($NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$dev=(isset($DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$exp=(isset($ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$sum=$nov+$dev+$exp;
						$sumnov+=$nov;
						$sumdev+=$dev;
						$sumexp+=$exp;
						$sumsum+=$sum;
					}
				}
			}
		}	
		echo "<tr style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#cacaca;'><th style='text-align:center;' width='20%'><b>Grand Total</b></th>";
		echo "<th style='text-align:center;' width='20%'>".$sumnov."</th>";
		echo "<th style='text-align:center;' width='20%'>".$sumdev."</th>";
		echo "<th style='text-align:center;' width='20%'>".$sumexp."</th>";
		echo "<th style='text-align:center;' width='20%'>".$sumsum."</th></tr>";
		echo "<tr style='border none !important;'><td colspan='5' style='border: none !important;'></td></tr></table>";
		foreach($SiteIN as $fsite){
		echo '<table class="table table-condensed" style="font-family:Helvetica; font-size:11px;width:100%;">';
		echo "<tr style='font-weight: bold;border: 1px solid #d7d7d7 ;background-color:#36CCF8'><td>Site Name:" . $fsite.'</td>';
		$loc = array_search($fsite, $SiteIN);
			$sumnov1=0; $sumdev1=0; $sumexp1=0; $sumsum1=0;
				foreach($AppIn[$loc] as $fservice){
					$appLoc = array_search($fservice, $AppIn[$loc]);
					$skillx = 0;
				foreach($SkillIn[$loc] as $fskill){
				
				$skiLoc = array_search($fskill, $SkillIn[$loc]);
				foreach($IntervalIn[$loc] as $finterval){
						$nov=0; $dev=0; $exp=0; $sum=0;
						$inLoc = array_search($finterval, $IntervalIn[$loc]);							
						$nov=(isset($NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$dev=(isset($DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$exp=(isset($ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$sum=$nov+$dev+$exp; $sumnov1+=$nov; $sumdev1+=$dev; $sumexp1+=$exp; $sumsum1+=$sum;
					}
				}		
			}
			echo "<th style='text-align:center;' width='20%'>".$sumnov1."</th>";
			echo "<th style='text-align:center;' width='20%'>".$sumdev1."</th>";
			echo "<th style='text-align:center;' width='20%'>".$sumexp1."</th>";
			echo "<th style='text-align:center;' width='20%'>".$sumsum1."</th></tr>";
			foreach($AppIn[$loc] as $fservice){
					$appLoc = array_search($fservice, $AppIn[$loc]);
				$skillx = 0;
				foreach($SkillIn[$loc] as $fskill){
				$skiLoc = array_search($fskill, $SkillIn[$loc]);
					$tbrow=""; $sumnov=0; $sumdev=0; $sumexp=0; $sumsum=0;
						foreach($IntervalIn[$loc] as $finterval){
						$nov=0; $dev=0; $exp=0; $sum=0;
						$inLoc = array_search($finterval, $IntervalIn[$loc]);							
						$nov=(isset($NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$dev=(isset($DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$exp=(isset($ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$sum=$nov+$dev+$exp;
						if($sum!=0){
							$tbrow.= "<tr>";
							$tbrow.= "<td style='text-align:center;'>".$finterval."</td>";
							$tbrow.= "<td style='text-align:center;'>".$nov."</td>";
							$tbrow.= "<td style='text-align:center;'>".$dev."</td>";
							$tbrow.= "<td style='text-align:center;'>".$exp."</td>";
							$tbrow.= "<td style='text-align:center;'>".$sum."</td>";
							$tbrow.= "</tr>";
						}
						$sumnov+=$nov; $sumdev+=$dev; $sumexp+=$exp; $sumsum+=$sum;
				}
				if($sumsum!=0){
					if($skillx == 0){
					$appLoc = array_search($fservice, $AppIn[$loc]);
					$skillx = 0; $sumnov2=0; $sumdev2=0; $sumexp2=0; $sumsum2=0;
					foreach($SkillIn[$loc] as $fskill){
					$skiLoc = array_search($fskill, $SkillIn[$loc]);
							foreach($IntervalIn[$loc] as $finterval){
							$nov=0; $dev=0; $exp=0; $sum=0;
							$inLoc = array_search($finterval, $IntervalIn[$loc]);							
							$nov=(isset($NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
							$dev=(isset($DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
							$exp=(isset($ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
							$sum=$nov+$dev+$exp; $sumnov2+=$nov; $sumdev2+=$dev; $sumexp2+=$exp; $sumsum2+=$sum;
						}
					}
						echo "<tr style='font-weight: bold;text-align:left;font-family: Helvetica;font-size: 11px;background-color:#B7EDFD;'><td style='text-align:left;'>Application:" . $fservice.'</td>';
						echo "<th style='text-align:center;' width='20%'>".$sumnov2."</th>";
						echo "<th style='text-align:center;' width='20%'>".$sumdev2."</th>";
						echo "<th style='text-align:center;' width='20%'>".$sumexp2."</th>";
						echo "<th style='text-align:center;' width='20%'>".$sumsum2."</th></tr>";
						$skillx ++;
					}
				echo "<tr style='font-weight: bold;text-align:left;font-family: Helvetica;font-size: 11px;background-color:#cacaca;'><td colspan='5'>SkillSet:" . $fskill.'</td></tr>';
				echo "<tr ><th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;' width='20%'>Interval</th>";
				echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;' width='20%'>NOVICE</th>";
				echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;' width='20%'>DEVELOPING EXPERT</th>";
				echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;' width='20%'>EXPERT</th>";
				echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;' width='20%'>Total Answered</th></tr>";
				echo $tbrow;
				echo "<tr style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#cacaca;'><th style='text-align:center;' width='20%'>Summary</th>";
				echo "<th style='text-align:center;'>".$sumnov."</th>";
				echo "<th style='text-align:center;'>".$sumdev."</th>";
				echo "<th style='text-align:center;'>".$sumexp."</th>";
				echo "<th style='text-align:center;'>".$sumsum."</th></tr>";
				echo "<tr style='border none !important;'><td colspan='5' style='border: none !important;'></td></tr>";
				}
			}
		}
		echo "</table>";
	}
$SiteIN=[];$IntervalIn=[]; $AppIn=[]; $SkillIn=[]; $count=[]; $NoviceAnswered=[]; $DevExpertAnswered=[]; $ExpertAnswered=[]; $ASA_Novice=[]; $ASA_DevExpert=[]; $ASA_Expert=[];
foreach($z3->result() as $row) {
		$interval="";
		if($freq=="Fifteen")
			{	
				$xdate = date('m/d/Y', strtotime($row->{'FifteenMinuteInterval'})); 
				$xtime = substr($row->{'15MinsInterval'}, -12);
				$xtime1 = date('H:i:s ',strtotime($xtime));
				$xtime2 = date('H:i:s',strtotime("+15 minutes", strtotime($xtime)));
				$xfinal= $xdate . ' ' .$xtime1 . ' - '.$xtime2; 
				$interval=$xfinal;
			}elseif($freq=="Thirty"){
				$xdate = date('m/d/Y', strtotime($row->{'FifteenMinuteInterval'})); 
				$xtime = substr($row->{'30MinsInterval'}, -12);
				$xtime1 = date('H:i:s ',strtotime($xtime));
				$xtime2 = date('H:i:s',strtotime("+30 minutes", strtotime($xtime)));
				$xfinal= $xdate . ' ' .$xtime1 . ' - '.$xtime2; 
				$interval=$xfinal;
			}elseif($freq=="Sixty"){
				$xdate = date('m/d/Y', strtotime($row->{'FifteenMinuteInterval'})); 
				$xtime = substr($row->{'60MinsInterval'}, -12);
				$xtime1 = date('H:i:s ',strtotime($xtime));
				$xtime2 = date('H:i:s',strtotime("+60 minutes", strtotime($xtime)));
				$xfinal= $xdate . ' ' .$xtime1 . ' - '.$xtime2; 
				$interval=$xfinal;
			}elseif($freq=="D"){
				$interval=$row->perDate;
			}elseif($freq=="W"){
				$interval=$row->{'Sunday'}.' - ' . $row->{'Saturday'};
			}elseif($freq=="M"){
				$interval=$row->{'MONTH_NAME'};
			}elseif($freq=="PM"){
				$interval=$row->{'MONTH_NAME'};
			}elseif($freq=="Y"){
				$interval=$row->YEAR;
			}elseif($freq=="Morning"){
				$pos = strpos($row->Morning,"6AM - 2PM");
				if($pos>0){ $interval=$row->Morning; }else{ continue; }
			}elseif($freq=="Afternoon"){
				$pos = strpos($row->Afternoon,"2PM - 10PM");
				if($pos>0){ $interval=$row->Afternoon;  }else{ continue; }
			}elseif($freq=="Graveyard"){
				$pos = strpos($row->Graveyard,"10PM - 6AM");
				if($pos>0){ $interval=$row->Graveyard; }else{ continue; }
			}																						
	if(!in_array($row->SiteName,$SiteIN)){ array_push($SiteIN, $row->SiteName); $siteLoc = array_search($row->SiteName, $SiteIN); $AppIn[ $siteLoc ] = []; $SkillIn[ $siteLoc ] = []; $IntervalIn[ $siteLoc ] = []; }else{ $siteLoc = array_search($row->SiteName, $SiteIN); }
	if(!in_array($row->Service_c,$AppIn[$siteLoc])){ array_push($AppIn[$siteLoc], $row->Service_c); }
	asort($AppIn[$siteLoc]);
	if(!in_array($row->Skill_Desc,$SkillIn[$siteLoc])){ array_push($SkillIn[$siteLoc], $row->Skill_Desc); }	
	asort($SkillIn[$siteLoc]);
	
	if(!in_array($interval,$IntervalIn[$siteLoc])){ array_push($IntervalIn[$siteLoc], $interval);}
	if($freq=="M" || $freq=="M"){}else{ asort($IntervalIn[$siteLoc]);};
	$appLoc = array_search($row->Service_c, $AppIn[$siteLoc]);
	$skiLoc = array_search($row->Skill_Desc, $SkillIn[$siteLoc]);
	$inLoc = array_search($interval, $IntervalIn[$siteLoc]);
	if(isset($count[$siteLoc][$appLoc][$skiLoc][$inLoc])){ $count[$siteLoc][$appLoc][$skiLoc][$inLoc]++; }else{ $count[$siteLoc][$appLoc][$skiLoc][$inLoc]=0;  }
	if(isset($NoviceAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc])){
		if($row->NoviceAnswered==''){ }else{ $NoviceAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]++; $ASA_Novice[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->ASA_Novice; }
	}else{
		$NoviceAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]=0;
		$ASA_Novice[$siteLoc][$appLoc][$skiLoc][$inLoc]=0;
		if($row->NoviceAnswered!=''){ $NoviceAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]++; $ASA_Novice[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->ASA_Novice; }
	}
	if(isset($DevExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc])){
		if($row->DevExpertAnswered==''){ }else{ $DevExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]++; $ASA_DevExpert[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->ASA_DevExpert; }
	}else{
		$DevExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]=0; $ASA_DevExpert[$siteLoc][$appLoc][$skiLoc][$inLoc]=0;
		if($row->DevExpertAnswered!=''){ $DevExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]++; $ASA_DevExpert[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->ASA_DevExpert; }
	}
	if(isset($ExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc])){
		if($row->ExpertAnswered==''){ }else{ $ExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]++; $ASA_Expert[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->ASA_Expert; }
	}else{
		$ExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]=0;
		$ASA_Expert[$siteLoc][$appLoc][$skiLoc][$inLoc]=0;
		if($row->ExpertAnswered!=''){ $ExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]++; $ASA_Expert[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->ASA_Expert;}
	}
}?>
		<br/><table width="100%"> <tr><th colspan="5" style="text-align:center;font-family:Helvetica;font-size:13px;">ASA per Skillset Application</th></tr> </table><br/>
			<?php
			asort($SiteIN);
			echo '<table class="table table-condensed" style="font-family:Helvetica; font-size:11px;width:100%">';
			echo "<tr ><th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#cacaca;' width='20%'></th>";
			echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#cacaca;' width='20%'>NOVICE</th>";
			echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#cacaca;' width='20%'>DEVELOPING EXPERT</th>";
			echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#cacaca;' width='20%'>EXPERT</th>";
			echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#cacaca;' width='20%'>Total ASA</th></tr>";
			$sumnov=0; $sumdev=0; $sumexp=0; $sumsum=0; $asasumnov=0; $asasumdev=0; $asasumexp=0; $asasumsum=0;
		/**** GRAND TOTAL ****/
		foreach($SiteIN as $fsite){
			$loc = array_search($fsite, $SiteIN);
			foreach($AppIn[$loc] as $fservice){
				$appLoc = array_search($fservice, $AppIn[$loc]);
				$skillx = 0;
				foreach($SkillIn[$loc] as $fskill){ 
				$skiLoc = array_search($fskill, $SkillIn[$loc]);
						$tbrow="";
						foreach($IntervalIn[$loc] as $finterval){
						$nov=0; $dev=0; $exp=0; $asanov=0; $asadev=0; $asaexp=0; $asasum=0; $sum=0;
						$inLoc = array_search($finterval, $IntervalIn[$loc]);							
						$nov=(isset($NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$dev=(isset($DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$exp=(isset($ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$asanov=(isset($ASA_Novice[$loc][$appLoc][$skiLoc][$inLoc]) ? $ASA_Novice[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$asadev=(isset($ASA_DevExpert[$loc][$appLoc][$skiLoc][$inLoc]) ? $ASA_DevExpert[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$asaexp=(isset($ASA_Expert[$loc][$appLoc][$skiLoc][$inLoc]) ? $ASA_Expert[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$sum=$nov+$dev+$exp; $asasum=$asanov+$asadev+$asaexp;
						$sumnov+=$nov; $sumdev+=$dev; $sumexp+=$exp; $sumsum+=$sum;
						$asasumnov+=$asanov; $asasumdev+=$asadev; $asasumexp+=$asaexp; $asasumsum+=$asasum;
					}
				}
			}
		}
		echo "<tr style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2;'><th style='text-align:center;' width='20%'><b>Grand Total</b></th>";
				if($sumnov!=0){ echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2;'  width='20%'>".secToHR(number_format(round($asasumnov/$sumnov),2))."</th>"; }else{ echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2;'  width='20%'>00:00:00</th>";}
				if($sumdev!=0){ echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2;' width='20%'>".secToHR(number_format(round($asasumdev/$sumdev),2))."</th>"; }else{echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2;' width='20%'>00:00:00</th>";}
				if($sumexp!=0){ echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2;' width='20%'>".secToHR(number_format(round($asasumexp/$sumexp),2))."</th>"; }else{ echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2;' width='20%'>00:00:00</th>"; }
				if($sumsum!=0){ echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2;' width='20%'>".secToHR(number_format(round($asasumsum/$sumsum),2))."</th>"; }else{ echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2;' width='20%'>00:00:00</th>";}
		echo "</tr></table>";
			
		/**** END OF GRAND TOTAL ****/
		foreach($SiteIN as $fsite){
		echo '<table class="table table-condensed" style="font-family:Helvetica; font-size:11px;width:100%">';
		echo "<tr style='font-weight: bold;border: 1px solid #d7d7d7 ;background-color:#36CCF8'><td>Site Name:" . $fsite.'</td>';
			$loc = array_search($fsite, $SiteIN);
			/*** START OF SITE SUMMARY ***/
			$sumnov1=0; $sumdev1=0; $sumexp1=0; $sumsum1=0; $asasumnov1=0; $asasumdev1=0; $asasumexp1=0; $asasumsum1=0;
			foreach($AppIn[$loc] as $fservice){
					$appLoc = array_search($fservice, $AppIn[$loc]);
				$skillx = 0;
				foreach($SkillIn[$loc] as $fskill){
				$skiLoc = array_search($fskill, $SkillIn[$loc]);
						$tbrow="";
						foreach($IntervalIn[$loc] as $finterval){
						$nov=0; $dev=0; $exp=0; $asanov=0; $asadev=0; $asaexp=0; $asasum=0; $sum=0;
						$inLoc = array_search($finterval, $IntervalIn[$loc]);							
						$nov=(isset($NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$dev=(isset($DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$exp=(isset($ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$asanov=(isset($ASA_Novice[$loc][$appLoc][$skiLoc][$inLoc]) ? $ASA_Novice[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$asadev=(isset($ASA_DevExpert[$loc][$appLoc][$skiLoc][$inLoc]) ? $ASA_DevExpert[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$asaexp=(isset($ASA_Expert[$loc][$appLoc][$skiLoc][$inLoc]) ? $ASA_Expert[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						
						$sum=$nov+$dev+$exp; $asasum=$asanov+$asadev+$asaexp;
						$sumnov1+=$nov; $sumdev1+=$dev; $sumexp1+=$exp; $sumsum1+=$sum;
						$asasumnov1+=$asanov; $asasumdev1+=$asadev; $asasumexp1+=$asaexp; $asasumsum1+=$asasum;
					}
				}
			}
			if($sumnov1!=0){ echo "<th style='text-align:center; width='20%''>".secToHR(number_format(round($asasumnov1/$sumnov1),2))."</th>"; }else{ echo "<th style='text-align:center; width='20%''>00:00:00</th>"; }
			if($sumdev1!=0){ echo "<th style='text-align:center; width='20%''>".secToHR(number_format(round($asasumdev1/$sumdev1),2))."</th>"; }else{ echo "<th style='text-align:center; width='20%''>00:00:00</th>"; }
			if($sumexp1!=0){ echo "<th style='text-align:center; width='20%''>".secToHR(number_format(round($asasumexp1/$sumexp1),2))."</th>"; }else{ echo "<th style='text-align:center; width='20%''>00:00:00</th>"; }
			if($sumsum1!=0){ echo "<th style='text-align:center; width='20%''>".secToHR(number_format(round($asasumsum1/$sumsum1),2))."</th>"; }else{ echo "<th style='text-align:center; width='20%''>00:00:00</th></tr>"; }
			
			foreach($AppIn[$loc] as $fservice){
				$appLoc = array_search($fservice, $AppIn[$loc]); $skillx = 0;
				foreach($SkillIn[$loc] as $fskill){				
				$skiLoc = array_search($fskill, $SkillIn[$loc]);
					$tbrow=""; $sumnov=0; $sumdev=0; $sumexp=0; $sumsum=0; $asasumnov=0; $asasumdev=0; $asasumexp=0; $asasumsum=0;
						foreach($IntervalIn[$loc] as $finterval){
						$nov=0; $dev=0; $exp=0; $asanov=0; $asadev=0; $asaexp=0; $asasum=0; $sum=0;
						$inLoc = array_search($finterval, $IntervalIn[$loc]);							
						$nov=(isset($NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$dev=(isset($DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$exp=(isset($ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$asanov=(isset($ASA_Novice[$loc][$appLoc][$skiLoc][$inLoc]) ? $ASA_Novice[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$asadev=(isset($ASA_DevExpert[$loc][$appLoc][$skiLoc][$inLoc]) ? $ASA_DevExpert[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$asaexp=(isset($ASA_Expert[$loc][$appLoc][$skiLoc][$inLoc]) ? $ASA_Expert[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						
						$sum=$nov+$dev+$exp; $asasum=$asanov+$asadev+$asaexp;
						if($sum!=0){
							$tbrow.= "<tr>";
							$tbrow.= "<td style='text-align:center;'>".$finterval."</td>";
							if($nov!=0){ $tbrow.= "<td style='text-align:center;'>".secToHR( number_format(round($asanov / $nov ),2))."</td>"; }else{ $tbrow.= "<td style='text-align:center;'>00:00:00</td>"; }	
							if($dev!=0){ $tbrow.= "<td style='text-align:center;'>".secToHR( number_format(round($asadev / $dev),2) )."</td>"; }else{ $tbrow.= "<td style='text-align:center;'>00:00:00</td>"; }	
							if($exp!=0){ $tbrow.= "<td style='text-align:center;'>".secToHR(number_format(round( $asaexp / $exp),2))."</td>"; }else{ $tbrow.= "<td style='text-align:center;'>00:00:00</td>"; }	
							if($sum!=0){ $tbrow.= "<td style='text-align:center;'>".secToHR( number_format(round($asasum / $sum),2))."</td>"; }else{ $tbrow.= "<td style='text-align:center;'>00:00:00</td>"; }	
							$tbrow.= "</tr>";
						}
						$sumnov+=$nov; $sumdev+=$dev; $sumexp+=$exp; $sumsum+=$sum;
						$asasumnov+=$asanov; $asasumdev+=$asadev; $asasumexp+=$asaexp; $asasumsum+=$asasum;
				}
				if($sumsum!=0){
					if($skillx == 0){
						$appLoc = array_search($fservice, $AppIn[$loc]);
						$skillx = 0;  $sumnov4 = 0; $sumdev4 = 0; $sumexp4 = 0; $sumsum4 = 0; $asasumnov4 = 0; $asasumdev4 = 0; $asasumexp4 = 0; $asasumsum4 = 0;
						foreach($SkillIn[$loc] as $fskill){
						$skiLoc = array_search($fskill, $SkillIn[$loc]);
							$tbrow="";
								foreach($IntervalIn[$loc] as $finterval){
								$nov=0; $dev=0; $exp=0; $asanov=0; $asadev=0; $asaexp=0; $asasum=0; $sum=0;
								$inLoc = array_search($finterval, $IntervalIn[$loc]);							
								$nov=(isset($NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
								$dev=(isset($DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
								$exp=(isset($ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
								$asanov=(isset($ASA_Novice[$loc][$appLoc][$skiLoc][$inLoc]) ? $ASA_Novice[$loc][$appLoc][$skiLoc][$inLoc] : 0);
								$asadev=(isset($ASA_DevExpert[$loc][$appLoc][$skiLoc][$inLoc]) ? $ASA_DevExpert[$loc][$appLoc][$skiLoc][$inLoc] : 0);
								$asaexp=(isset($ASA_Expert[$loc][$appLoc][$skiLoc][$inLoc]) ? $ASA_Expert[$loc][$appLoc][$skiLoc][$inLoc] : 0);
								$sum=$nov+$dev+$exp; $asasum=$asanov+$asadev+$asaexp; $sumnov4+=$nov; $sumdev4+=$dev; $sumexp4+=$exp; $sumsum4+=$sum; $asasumnov4+=$asanov; $asasumdev4+=$asadev; $asasumexp4+=$asaexp; $asasumsum4+=$asasum;
							}
						}
						echo "<tr style='font-weight: bold;text-align:left;font-family: Helvetica;font-size: 11px;background-color:#B7EDFD;'><td style='text-align:left;'>Application:" . $fservice.'</td>';
						if($sumnov4!=0){ echo "<td style='text-align:center; width='20%''>".secToHR(number_format(round($asasumnov4/$sumnov4),2))."</td>"; }else{ echo "<td style='text-align:center; width='20%''>00:00:00</td>"; }
						if($sumdev4!=0){ echo "<td style='text-align:center; width='20%''>".secToHR(number_format(round($asasumdev4/$sumdev4),2))."</td>"; }else{ echo "<td style='text-align:center; width='20%''>00:00:00</td>"; }
						if($sumexp4!=0){ echo "<td style='text-align:center; width='20%''>".secToHR(number_format(round($asasumexp4/$sumexp4),2))."</td>"; }else{ echo "<td style='text-align:center; width='20%''>00:00:00</td>"; }
						if($sumsum4!=0){ echo "<td style='text-align:center; width='20%''>".secToHR(number_format(round($asasumsum4/$sumsum4),2))."</td>"; }else{ echo "<td style='text-align:center; width='20%''>00:00:00</td>"; }
						$skillx ++;
					}
				echo "<tr style='font-weight: bold;text-align:left;font-family: Helvetica;font-size: 11px;background-color:#d3eff7;'><td colspan='5'>SkillSet:" . $fskill.'</td></tr>';
				echo "<tr ><th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;' width='20%'>Interval</th>";
				echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;' width='20%'>NOVICE</th>";
				echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;' width='20%'>DEVELOPING EXPERT</th>";
				echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;' width='20%'>EXPERT</th>";
				echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;' width='20%'>Total ASA</th></tr>";
				echo $tbrow;
				echo "<tr style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#cacaca;'><th style='text-align:center;'>Summary</th>";
				if($sumnov!=0){ echo "<th style='text-align:center; width='20%''>".secToHR(number_format(round($asasumnov/$sumnov),2))."</th>"; }else{ echo "<th style='text-align:center; width='20%''>00:00:00</th>"; }
				if($sumdev!=0){ echo "<th style='text-align:center; width='20%''>".secToHR(number_format(round($asasumdev/$sumdev),2))."</th>"; }else{ echo "<th style='text-align:center; width='20%''>00:00:00</th>"; }
				if($sumexp!=0){ echo "<th style='text-align:center; width='20%''>".secToHR(number_format(round($asasumexp/$sumexp),2))."</th>"; }else{ echo "<th style='text-align:center; width='20%''>00:00:00</th>"; }
				if($sumsum!=0){ echo "<th style='text-align:center; width='20%''>".secToHR(number_format(round($asasumsum/$sumsum),2))."</th>"; }else{ echo "<th style='text-align:center; width='20%''>00:00:00</th>"; }
				echo "<tr style='border: none !important;'><td colspan='5' style='border: none !important;'></td></tr>";
				}
			}
		}
		echo "</table>";
	}?><br/>
	<table width="100%"><tr><th colspan="10" style="text-align:center;font-family:Helvetica;font-size:13px;">Offered vs. Answered per Application</th></tr></table><br/>
	<?php  $SiteIN=[];$IntervalIn=[]; $AppIn=[]; $SkillIn=[]; $count=[]; $NoviceAnswered=[]; $DevExpertAnswered=[]; $ExpertAnswered=[]; $Novice_Offered=[]; $DevExpert_Offered=[]; $Expert_Offered=[];
foreach($z2->result() as $row) {
		$interval="";
		if($freq=="Fifteen")
			{	
				$xdate = date('m/d/Y', strtotime($row->{'FifteenMinuteInterval'})); 
				$xtime = substr($row->{'15MinsInterval'}, -12);
				$xtime1 = date('H:i:s',strtotime($xtime));
				$xtime2 =  date('H:i:s',strtotime("+15 minutes", strtotime($xtime)));
				$xfinal= $xdate . ' ' .$xtime1 . ' - '.$xtime2; 
				$interval=$xfinal;
			}elseif($freq=="Thirty"){
				$xdate = date('m/d/Y', strtotime($row->{'FifteenMinuteInterval'})); 
				$xtime = substr($row->{'30MinsInterval'}, -12);
				$xtime1 = date('H:i:s',strtotime($xtime));
				$xtime2 =  date('H:i:s',strtotime("+30 minutes", strtotime($xtime)));
				$xfinal= $xdate . ' ' .$xtime1 . ' - '.$xtime2; 
				$interval=$xfinal;
			}elseif($freq=="Sixty"){
				$xdate = date('m/d/Y', strtotime($row->{'FifteenMinuteInterval'})); 
				$xtime = substr($row->{'60MinsInterval'}, -12);
				$xtime1 = date('H:i:s',strtotime($xtime));
				$xtime2 =  date('H:i:s',strtotime("+60 minutes", strtotime($xtime)));
				$xfinal= $xdate . ' ' .$xtime1 . ' - '.$xtime2; 
				$interval=$xfinal;
			}elseif($freq=="D"){
				$interval=$row->perDate;
			}elseif($freq=="W"){
				$interval=$row->{'Sunday'}.' - ' . $row->{'Saturday'};
			}elseif($freq=="M"){
				$interval=$row->{'MONTH_NAME'};
			}elseif($freq=="PM"){
				$interval=$row->{'MONTH_NAME'};
			}elseif($freq=="Y"){
				$interval=$row->YEAR;
			}elseif($freq=="Morning"){
				if($row->Morning==''){ continue;}
				$interval= $row->Morning;
			}elseif($freq=="Afternoon"){
				if($row->Afternoon==''){ continue; }
				$interval= $row->Afternoon;
			}elseif($freq=="Graveyard"){
				if($row->Graveyard==''){ continue; }
				$interval= $row->Graveyard;
			}
			
	if(!in_array($row->SiteName,$SiteIN)){
		array_push($SiteIN, $row->SiteName);
		$siteLoc = array_search($row->SiteName, $SiteIN);
		$AppIn[ $siteLoc ] = [];
		$SkillIn[ $siteLoc ] = [];
		$IntervalIn[ $siteLoc ] = [];	
	}else{
		$siteLoc = array_search($row->SiteName, $SiteIN);
	}

	if(!in_array($row->Service_c,$AppIn[$siteLoc])){ array_push($AppIn[$siteLoc], $row->Service_c); }
	if(!in_array($row->Skill_Desc,$SkillIn[$siteLoc])){ array_push($SkillIn[$siteLoc], $row->Skill_Desc); }	
	if(!in_array($interval,$IntervalIn[$siteLoc])){array_push($IntervalIn[$siteLoc], $interval);}
	$appLoc = array_search($row->Service_c, $AppIn[$siteLoc]);
	$skiLoc = array_search($row->Skill_Desc, $SkillIn[$siteLoc]);
	$inLoc = array_search($interval, $IntervalIn[$siteLoc]);
	if(isset($count[$siteLoc][$appLoc][$skiLoc][$inLoc])){ $count[$siteLoc][$appLoc][$skiLoc][$inLoc]++; }else{$count[$siteLoc][$appLoc][$skiLoc][$inLoc]=0;}
	if(isset($NoviceAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc])){
		if($row->NoviceAnswered==''){ }else{$NoviceAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->NoviceAnswered; }
	}else{
		$NoviceAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]=0;
		if($row->NoviceAnswered!='' ){ $NoviceAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->NoviceAnswered; }
	}
	if(isset($DevExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc])){
		if($row->DevExpertAnswered==''){ }else{ $DevExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->DevExpertAnswered; }
	}else{
		$DevExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]=0;
		if($row->DevExpertAnswered!='' ){ $DevExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->DevExpertAnswered; }
	}
	if(isset($ExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc])){
		if($row->ExpertAnswered==''){ }else{ $ExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->ExpertAnswered; }
	}else{
		$ExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]=0;
		if($row->ExpertAnswered!='' ){ $ExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->ExpertAnswered; }
	}
	
	if(isset($Novice_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc])){
		if($row->Novice_Offered==''){ }else{ $Novice_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->Novice_Offered;}
	}else{
		$Novice_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc]=0;
		if($row->Novice_Offered!=''  ){ $Novice_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->Novice_Offered; }	
	}
	if(isset($DevExpert_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc])){
		if($row->DevExpert_Offered==''){ }else{ $DevExpert_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->DevExpert_Offered; }
	}else{
		$DevExpert_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc]=0;
		if($row->DevExpert_Offered!=''  ){ $DevExpert_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->DevExpert_Offered; }
	}
	if(isset($Expert_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc])){
		if($row->Expert_Offered==''){ }else{ $Expert_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->Expert_Offered; }
		if($row->AnsUser_Id==''){
			if($row->Answered==""){ $Expert_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc]++; }
		}
	}else{
		$Expert_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc]=0;
		if($row->Expert_Offered!=''  ){ $Expert_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->Expert_Offered; }
		if($row->AnsUser_Id=='' ){
			if(  $row->Answered==""){$Expert_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc]++;}
		}
	}
}
	echo '<table class="table table-condensed"  style="font-family:Helvetica; font-size:11px;width:100%;">';		
		$sumnov=0; $sumdev=0; $sumexp=0; $sumsum=0; $sumnovo=0; $sumdevo=0; $sumexpo=0; $sumsumo=0;
		foreach($SiteIN as $fsite){
			$loc = array_search($fsite, $SiteIN);
			asort($AppIn[$loc]);
				$xsite = "";
			foreach($AppIn[$loc] as $fservice){
					$appLoc = array_search($fservice, $AppIn[$loc]);
				$xservice = ""; $xskill = "";
				asort($SkillIn[$loc]);
				foreach($SkillIn[$loc] as $fskill){
				$skiLoc = array_search($fskill, $SkillIn[$loc]);
					foreach($IntervalIn[$loc] as $finterval){
						$nov=0; $dev=0; $exp=0; $sum=0; $novo=0; $devo=0; $expo=0; $sumo=0;
						$inLoc = array_search($finterval, $IntervalIn[$loc]);							
						$nov=(isset($NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$dev=(isset($DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$exp=(isset($ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$novo=(isset($Novice_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $Novice_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$devo=(isset($DevExpert_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpert_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$expo=(isset($Expert_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $Expert_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$sum=$nov+$dev+$exp;
						$sumo=$novo+$devo+$expo;
						$sumnov+=$nov; $sumdev+=$dev; $sumexp+=$exp; $sumsum+=$sum; $sumnovo+=$novo; $sumdevo+=$devo; $sumexpo+=$expo; $sumsumo+=$sumo;
					}
				}
			}
		}
		echo "<tr style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#cacaca;font-weight:bold;'> <th width='10%'></th>";
		echo "<th width='10%'>OFFERED</th>";
		echo "<th width='10%'>ANSWERED</th>";
		echo "<th width='10%'>AL</th>";
		echo "<th width='10%'>OFFERED</th>";
		echo "<th width='10%'>ANSWERED</th>";
		echo "<th width='5%'>AL</th>";
		echo "<th width='5%'>OFFERED</th>";
		echo "<th width='12%'>ANSWERED</th>";
		echo "<th width='18%'>AL</th>";
		echo "</tr>";
		echo "<tr style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2;'><th><b>Grand Total</b></th>";
		echo "<th>".$sumnovo."</th>";
		echo "<th>".$sumnov."</th>";
		echo "<th>".($sumnov==0? '0.00' : number_format((float)$sumnov/$sumnovo *100, 2, '.', ''))."%</th>";
		echo "<th>".$sumdevo."</th>";
		echo "<th>".$sumdev."</th>";
		echo "<th>".($sumdev==0?  '0.00' : number_format((float)$sumdev/$sumdevo *100, 2, '.', ''))."%</th>";
		echo "<th>".$sumexpo."</th>";
		echo "<th>".$sumexp."</th>";
		echo "<th>".($sumexp==0?  '0.00' : number_format((float)$sumexp/$sumexpo *100, 2, '.', ''))."%</th>";
		echo "</tr>";
		echo'<tr colspan="10" style="border:1px solid #fff !important;"><td style="border:1px solid #fff !important;"></td></tr>';	
		asort($SiteIN);
		foreach($SiteIN as $fsite){
			$loc = array_search($fsite, $SiteIN);
			asort($AppIn[$loc]);
				$xsite = "";
				if($xsite != $fsite){
				echo "<tr style='font-weight: bold;border: 1px solid #d7d7d7 ;background-color:#36CCF8'><th>Site Name:" . $fsite.'</th>';
				$xsite = $fsite;
				}
				/**** START OF SITE SUMMARY ***/
					$sumnov1=0; $sumdev1=0; $sumexp1=0; $sumsum1=0; $sumnovo1=0; $sumdevo1=0; $sumexpo1=0; $sumsumo1=0;
				foreach($AppIn[$loc] as $fservice){
					$appLoc = array_search($fservice, $AppIn[$loc]);
					asort($SkillIn[$loc]);
				foreach($SkillIn[$loc] as $fskill){
				$skiLoc = array_search($fskill, $SkillIn[$loc]);
						foreach($IntervalIn[$loc] as $finterval){
						$nov=0; $dev=0; $exp=0; $sum=0; $novo=0; $devo=0; $expo=0; $sumo=0;
						$inLoc = array_search($finterval, $IntervalIn[$loc]);							
						$nov=(isset($NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$dev=(isset($DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$exp=(isset($ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$novo=(isset($Novice_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $Novice_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$devo=(isset($DevExpert_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpert_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$expo=(isset($Expert_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $Expert_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$sum=$nov+$dev+$exp;
						$sumo=$novo+$devo+$expo;
						$sumnov1+=$nov; $sumdev1+=$dev; $sumexp1+=$exp; $sumsum1+=$sum; $sumnovo1+=$novo; $sumdevo1+=$devo; $sumexpo1+=$expo; $sumsumo1+=$sumo;
					}
				}
			}
				echo "<th style='text-align:center'>".$sumnovo1."</th>";
				echo "<th style='text-align:center'>".$sumnov1."</th>";
				echo "<th style='text-align:center'>".($sumnov1==0? '0.00' : number_format((float)$sumnov1/$sumnovo1 *100, 2, '.', ''))."%</th>";
				echo "<th style='text-align:center'>".$sumdevo1."</th>";
				echo "<th style='text-align:center'>".$sumdev1."</th>";
				echo "<th style='text-align:center'>".($sumdev1==0?  '0.00' : number_format((float)$sumdev1/$sumdevo1 *100, 2, '.', ''))."%</th>";
				echo "<th style='text-align:center'>".$sumexpo1."</th>";
				echo "<th style='text-align:center'>".$sumexp1."</th>";
				echo "<th style='text-align:center'>".($sumexp1==0?  '0.00' : number_format((float)$sumexp1/$sumexpo1 *100, 2, '.', ''))."%</th>";
				echo "</tr>";
				/*** END OF SITE SUMMARY ***/
			foreach($AppIn[$loc] as $fservice){
					$appLoc = array_search($fservice, $AppIn[$loc]);
				$xservice = "";
				$xskill = "";
				asort($SkillIn[$loc]);
				foreach($SkillIn[$loc] as $fskill){
				$skiLoc = array_search($fskill, $SkillIn[$loc]);
					$tbrow=""; $sumnov=0; $sumdev=0; $sumexp=0; $sumsum=0; $sumnovo=0; $sumdevo=0; $sumexpo=0; $sumsumo=0;
					foreach($IntervalIn[$loc] as $finterval){
						$nov=0; $dev=0; $exp=0; $sum=0; $novo=0; $devo=0; $expo=0; $sumo=0;
						$inLoc = array_search($finterval, $IntervalIn[$loc]);							
						$nov=(isset($NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$dev=(isset($DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$exp=(isset($ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$novo=(isset($Novice_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $Novice_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$devo=(isset($DevExpert_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpert_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$expo=(isset($Expert_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $Expert_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$sum=$nov+$dev+$exp;
						$sumo=$novo+$devo+$expo;
						if($sumo!=0){
							$tbrow.= "<tr style='text-align:left;font-family: Helvetica;font-size: 11px;'>";
							$tbrow.= "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$finterval."</td>";
							$tbrow.= "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$novo."</td>";
							$tbrow.= "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$nov."</td>";
							$tbrow.= "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".($nov==0? '0.00' : number_format((float)$nov/$novo *100, 2, '.', ''))."%</td>";
							$tbrow.= "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$devo."</td>";
							$tbrow.= "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$dev."</td>";
							$tbrow.= "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".($dev==0? '0.00' : number_format((float)$dev/$devo *100, 2, '.', ''))."%</td>";
							$tbrow.= "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$expo."</td>";
							$tbrow.= "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$exp."</td>";
							$tbrow.= "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".($exp==0? '0.00' : number_format((float)$exp/$expo *100, 2, '.', ''))."%</td>";
							$tbrow.= "</tr>";
						}
						$sumnov+=$nov; $sumdev+=$dev; $sumexp+=$exp; $sumsum+=$sum; $sumnovo+=$novo; $sumdevo+=$devo; $sumexpo+=$expo; $sumsumo+=$sumo;
				}
				if($sumsumo!=0){
				if($xservice != $fservice){
					$xservice = $fservice;
					$sumnov5=0; $sumdev5=0; $sumexp5=0; $sumsum5=0; $sumnovo5=0; $sumdevo5=0; $sumexpo5=0; $sumsumo5=0;
					$appLoc = array_search($fservice, $AppIn[$loc]);
					asort($SkillIn[$loc]);
					foreach($SkillIn[$loc] as $fskill){
					$skiLoc = array_search($fskill, $SkillIn[$loc]);
						foreach($IntervalIn[$loc] as $finterval){
							$nov=0; $dev=0; $exp=0; $sum=0; $novo=0; $devo=0; $expo=0; $sumo=0;
							$inLoc = array_search($finterval, $IntervalIn[$loc]);							
							$nov=(isset($NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
							$dev=(isset($DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
							$exp=(isset($ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
							$novo=(isset($Novice_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $Novice_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
							$devo=(isset($DevExpert_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpert_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
							$expo=(isset($Expert_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $Expert_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
							$sum=$nov+$dev+$exp;
							$sumo=$novo+$devo+$expo; $sumnov5+=$nov; $sumdev5+=$dev; $sumexp5+=$exp; $sumsum5+=$sum; $sumnovo5+=$novo; $sumdevo5+=$devo; $sumexpo5+=$expo; $sumsumo5+=$sumo;
						}
					}
					echo "<tr style='font-weight: bold;text-align:left;font-family: Helvetica;font-size: 11px;background-color:#B7EDFD;'><td style='text-align:left;' >Application:" . $fservice.'</td>';
					echo "<th style='text-align:center'>".$sumnovo5."</th>";
					echo "<th style='text-align:center'>".$sumnov5."</th>";
					echo "<th style='text-align:center'>".($sumnov5==0? '0.00' : number_format((float)$sumnov5/$sumnovo5 *100, 2, '.', ''))."%</th>";
					echo "<th style='text-align:center'>".$sumdevo5."</th>";
					echo "<th style='text-align:center'>".$sumdev5."</th>";
					echo "<th style='text-align:center'>".($sumdev5==0?  '0.00' : number_format((float)$sumdev5/$sumdevo5 *100, 2, '.', ''))."%</th>";
					echo "<th style='text-align:center'>".$sumexpo5."</th>";
					echo "<th style='text-align:center'>".$sumexp5."</th>";
					echo "<th style='text-align:center'>".($sumexp5==0?  '0.00' : number_format((float)$sumexp5/$sumexpo5 *100, 2, '.', ''))."%</th>";
				}
				if($xskill != $fskill){
				echo "<tr style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#cacaca;'><td width='20%' style='font-weight: bold;text-align:left;font-family: Helvetica;font-size: 11px;background-color:#cacaca;'>SkillSet:" . $fskill.'</td>';
				$xskill = $fskill;
				}
				echo "<th width='5%'></th><th width='15%'>NOVICE</th><th width='5%'></th>";
				echo "<th width='5%'></th><th width='15%'>DEVELOPING EXPERT</th><th width='5%'></th>";
				echo "<th></th><th  width='30%'>EXPERT</th><th width='5%'></th>";
				echo "</tr>";
				echo "<tr style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;font-weight:bold;'>
				<th width='10%'>Interval</th>";
				echo "<th width='10%'>OFFERED</th>";
				echo "<th width='10%'>ANSWERED</th>";
				echo "<th width='10%'>AL</th>";
				echo "<th width='10%'>OFFERED</th>";
				echo "<th width='10%'>ANSWERED</th>";
				echo "<th width='5%'>AL</th>";
				echo "<th width='5%'>OFFERED</th>";
				echo "<th width='12%'>ANSWERED</th>";
				echo "<th width='18%'>AL</th>";
				echo "</tr>";
				echo $tbrow;
							echo "<tr style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#cacaca;'><th>Summary</th>";
							echo "<th>".$sumnovo."</th>";
							echo "<th>".$sumnov."</th>";
							echo "<th>".($sumnov==0? '0.00' : number_format((float)$sumnov/$sumnovo *100, 2, '.', ''))."%</th>";
							echo "<th>".$sumdevo."</th>";
							echo "<th>".$sumdev."</th>";
							echo "<th>".($sumdev==0?  '0.00' : number_format((float)$sumdev/$sumdevo *100, 2, '.', ''))."%</th>";
							echo "<th>".$sumexpo."</th>";
							echo "<th>".$sumexp."</th>";
							echo "<th>".($sumexp==0?  '0.00' : number_format((float)$sumexp/$sumexpo *100, 2, '.', ''))."%</th>";
							echo "</tr>";
							echo'<tr colspan="10" style="border:1px solid #fff !important;"><td style="border:1px solid #fff !important;"></td></tr>';
				}
			}
			echo '<tr colspan="10" style="border:1px solid #fff !important;"><td style="border:1px solid #fff !important;"></td></tr>';
		}
	}
	echo "</table>";			
	?>
	</div> <br/>
	<div id="printer"><?php echo "<span style='font-family:Helvetica;font-size:11px;'>Printed By: " . $this->session->userdata('Fullname') . ": " .date("m/d/Y H:i:s") . '</span>'; ?></div>
</span>
	<div id="footer">
	<br><h4>Export:</h4>
		<div id="buttons">
			<input type="button" class="btn btn-success" onclick="exportPDF();" value="PDF">
			<input type="button" class="btn btn-success" onclick="exportXLS()" value="EXCEL">
			<input type="button" class="btn btn-success" onclick="exportCSV()" value="CSV">
			<input type="button" class="btn btn-success" onclick="exportDOC();" value="WORD">
			<input type="button" class="btn btn-success" onclick="exportHTML();" value="HTML">
		</div>
		<form id="exportPDF" action="<?php echo base_url("index.php/report_controller_z/generatePDF"); ?>" method="post" target="_blank">
			<textarea id="pdfdata" name="html" style="display:none"></textarea>
			<input type="hidden" name="orient" value='1'>
		</form>
		<form id="exportXLS" action="<?php echo base_url("index.php/report_controller_z/generateXLS"); ?>" method="post" target="_blank">
			<textarea id="exceldata" name="html" style="display:none"></textarea>
			<textarea id="excelcss" name="css" style="display:none"></textarea>
		</form>
		<form id="exportDOC" action="<?php echo base_url("index.php/report_controller_z/generateDOC"); ?>" method="post" target="_blank">
			<textarea id="docdata" name="html" style="display:none"></textarea>
			<input type="hidden" name="orient" value='1'>
			<textarea id="htmlcss" name="css" style="display:none"></textarea>
		</form>
		<form id="exportHTML" action="<?php echo base_url("index.php/report_controller_z/generateHTML"); ?>" method="post" target="_blank">
			<textarea id="htmldata" name="html" style="display:none"></textarea>
		</form>
	</div>
	<div id="lol" style="display:none;"></div>
	<div id="css" style="display:none;">
	#vhis28 tr th { font-family: Arial; text-align:center; vertical-align: middle; text-decoration:underline; font-weight:bold; color:#000; font-size:12px; }
	#vhis28 tr td { font-family: Arial; color:#000; font-size:10px; padding:5px; }
	#rcode { font-family: Arial; font-weight:bolder; font-size:11px; text-align:left; float:left; }
	#rdate { font-family: Arial; font-weight:bolder; font-size:11px; text-align:right; float:right; }
	#rtitle { font-family: Arial; font-size:12px; font-weight:bolder; text-align:center; margin:0 0 30px; }
	#rtitle p {font-family: Arial; font-size:24px; font-weight:bold; text-align:center; margin:20px 0 30px; }
	#dataResult { font-family: Arial;text-align:center; }
	#dataSummary { font-family: Arial;text-align:center;background-color:#eee;font-size:14px !important;font-weight:bold; }
	#footer { font-family: Arial;padding:20px 0; }
	#printer { font-family: Arial;font-size:13px; }</div>
<script>
	function exportXLS() {
		$("#lol").text($("#amazing").html());
		$("#exceldata").text($("#lol").text());
		$("#excelcss").text($("#css").text());
		setTimeout(function() { $("#exportXLS").submit(); },500);
	}
	function exportPDF() {
		$("#lol").text($("#amazing").html());
		$("#pdfdata").text($("#lol").text());
		setTimeout(function() { $("#exportPDF").submit(); },500);
	}
	function exportDOC() {
		$("#lol").text($("#amazing").html());
		$("#docdata").text($("#lol").text());
		$("#htmlcss").text($("#css").text());
		setTimeout(function() { $("#exportDOC").submit(); },500);
	}
	function exportHTML() {
		$("#lol").text($("#amazing").html());
		$("#htmldata").text($("#lol").text());
		setTimeout(function() { $("#exportHTML").submit(); },500);
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
			csv.push(row.join(","));        
		}
		downloadCSV(csv.join("\n"), filename);
	}
</script>
</body>
</html>
	

	