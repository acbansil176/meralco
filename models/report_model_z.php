<?php

if (!defined('BASEPATH'))
exit('No direct script access allowed');
	
class report_model_z extends CI_Model {
	
	public function __construct() {
		$this->db = $this->load->database('default', TRUE);
	}
	public function get_report_12($datefrom,$dateto,$site,$serviceid,$skillid){
		$site = rtrim($site,",");
		$sites = explode(",",$site);
		$sid = "";
		foreach($sites as $sitelist){
			$sid .= "'".$sitelist."',";
		}
		$sid =  rtrim($sid,",");
		$serviceid = rtrim($serviceid,",");
		$serviceids = explode(",",$serviceid);
		$svd = "";
		foreach($serviceids as $serviceidlist){
			$svd .= "'".$serviceidlist."',";
		}
		$svd =  rtrim($svd,",");
		$skillid = rtrim($skillid,",");
		$skillids = explode(",",$skillid);
		$skd = "";
		foreach($skillids as $skillidlist){
			$skd .= "'".$skillidlist."',";
		}
		$skd =  rtrim($skd,",");
		$cmd = "DECLARE @FromDateTime datetime; DECLARE @ToDateTime datetime;
		SET @FromDateTime='".$datefrom."'; SET @ToDateTime='".$dateto."';

		with DateRange([15MinsInterval]) as 
			(select Dateadd(minute, 0, @FromDateTime) union all select Dateadd(minute, 15, [15MinsInterval]) from DateRange where Dateadd(minute, 0, [15MinsInterval]) < Dateadd(minute, -15, @ToDateTime)
		),

		incoming_calls as (
		select distinct(acdCallDetail.SeqNum) as seqnum,dateadd(hour, 8, dateadd(minute, datediff(minute, 0, dateadd(minute, 0, acdCallDetail.CallStartDt)) / 15 * 15, 0)) as FifteenMinutesInterval,
		acdCallDetail.CallStartDt as callstartdt,acdCallDetail.CallId as callid,acdCallDetail.CallTypeId as calltypeid,acdCallDetail.CallActionId as callactionid,acdCallDetail.CallActionReasonId as callactionreasonid,
		acdCallDetail.User_Id as [user_id],	acdCallDetail.Station as station,acdCallDetail.ANI as ani,acdCallDetail.Service_Id as service_id,[service].Service_c as service_c,asbr.skill_id,
		media.Param1 as [sin],media.Param2 as contactnumber,media.Param5 as param5,media.Param9 as param9,media.Param15 as dnis,acdCallDetail.QueueStartDt as queuestartdt,acdCallDetail.QueueEndDt as queueenddt,
		acdCallDetail.WrapEndDt as wrapenddt,isnull(sites.SiteName, 'MOC') as sitename from REPDB..ACDCallDetail acdCallDetail
		left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Service] [service] on acdCallDetail.Service_Id = [service].Service_Id
		left join REPDB..MediaDataDetail media on acdCallDetail.SeqNum = media.SeqNum and acdCallDetail.callid = media.CallId
		left join repdb.dbo.ASBRCallSkillDetail asbr on asbr.SeqNum = acdCallDetail.SeqNum and asbr.CallStartDt = acdCallDetail.CallStartDt and asbr.Skill_Id not in (4000001, 4000002)
		left join RepUIDB..Stations stations on acdCallDetail.Station = stations.Station
		left join RepUIDB..Sites sites on stations.SiteGuid = sites.SiteGuid -- dnis.SiteGuid = sites.SiteGuid
		where dateadd(hour, 8, acdCallDetail.CallStartDt) >= dateadd(d, 0, @FromDateTime) and  dateadd(hour, 8, acdCallDetail.CallStartDt) <= dateadd(d, 0, @ToDateTime)
		),
		incoming_calls_skills as (select incoming.*, (select top 1 Skill_Desc from [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].Skills where Skill_Id = incoming.skill_id) as skill_desc from incoming_calls incoming),
		
		handled_base as (
			select	incoming.FifteenMinutesInterval,incoming.callstartdt,incoming.seqnum,incoming.callid,incoming.callactionid,incoming.[sin],incoming.[ani],incoming.service_id,incoming.service_c,incoming.skill_id,
			incoming.skill_desc,incoming.param5,incoming.param9,incoming.dnis,incoming.SiteName,incoming.[user_id],incoming.station,datediff(s, incoming.queuestartdt, incoming.queueenddt) as answerdelay,
			datediff(s, incoming.queuestartdt, incoming.queueenddt) as skillsetanswerdelay,	datediff(s, incoming.queueenddt, incoming.wrapenddt) as handlingtime
			from incoming_calls_skills incoming	where incoming.callactionid in (3, 8,1) and queuestartdt is not null
		),

		abandoned_base as (
			select	incoming.FifteenMinutesInterval,incoming.callstartdt,incoming.seqnum,incoming.callid,incoming.callactionid,incoming.[sin],incoming.[ani],incoming.service_id,incoming.service_c,incoming.skill_id,
			incoming.skill_desc,incoming.param5,incoming.param9,incoming.dnis,incoming.SiteName,incoming.[user_id],incoming.station,datediff(s, incoming.queuestartdt, incoming.queueenddt) as abandondelay,
			0 as skillsetabandondelay,0 as handlingtime	FROM incoming_calls_skills incoming	left join RepUIDB..DNIS d on incoming.dnis = d.DNIS	left join RepUIDB..Sites s on d.SiteGuid = s.SiteGuid
			WHERE incoming.callactionid IN (5,6)
		),

		overflowed_base as (
			select	incoming.FifteenMinutesInterval,incoming.callstartdt,incoming.seqnum,incoming.callid,incoming.callactionid,incoming.[sin],incoming.[ani],incoming.service_id,incoming.service_c,incoming.skill_id,incoming.skill_desc,
			incoming.param5,incoming.param9,incoming.dnis,incoming.SiteName,incoming.[user_id],incoming.station,datediff(s, incoming.queuestartdt, incoming.queueenddt) as overflowdelay,
			datediff(s, incoming.queuestartdt, incoming.queueenddt) as skillsetoverflowdelay,0 as handlingtime from incoming_calls_skills incoming where incoming.callactionid = 18
		),
		
		agent_site_login as (
			select	l.LoginDt,l.LogoutDt,l.User_Id,s.Station,sites.SiteName as agent_site,[service].Service_Id as service_id,[service].Service_c as service_c from	REPDB..AgentLoginLogout l
			left join REPDB..AgentStateAudit s on l.User_Id = s.User_Id and DATEADD(ms, -datepart(ms, l.LoginDt), l.LoginDt) = dateadd(ms, -datepart(ms, s.Status_Start_dt), s.Status_Start_dt)
			left join RepUIDB..Stations station on s.Station = station.Station left join RepUIDB..Sites sites on station.SiteGuid = sites.SiteGuid
			left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Service] [service] on l.Service_Id = [service].Service_Id
			where s.Agent_Index is not null	and (dateadd(hour, 8, l.LoginDt) between @FromDateTime and @ToDateTime or (dateadd(hour, 8, l.LoginDt) between @FromDateTime and @ToDateTime or l.LogoutDt is null))
		),

		abandoned_agent_site as (
			select	ab.FifteenMinutesInterval,ab.callstartdt,ab.service_id,ab.service_c,null as skill_id,null as skill_desc,ab.seqnum,ab.callid,ab.callactionid,ab.ani,ab.dnis,ab.sitename,'' as station,isnull(agt.agent_site, 'MOC') as proper_site_tagging,
			count(distinct agt.User_Id) as number_of_online_agents,0 as answered_flag,0 as overflow_flag,ab.param5 as last_ivr,ab.[sin],0 as repeat_count,ab.abandondelay as [delay],ab.skillsetabandondelay as [skillsetdelay],
			ab.handlingtime as [handlingtime],case when ab.abandondelay <= asbr.TargetQTime then 1 else 0 end as WinThreshold from	abandoned_base ab
			left join agent_site_login agt on (ab.callstartdt between agt.LoginDt and agt.LogoutDt or ab.callstartdt >= agt.LoginDt and agt.LogoutDt is null) and ab.SiteName = agt.agent_site -- or (ab.callstartdt >= agt.LoginDt and agt.LogoutDt is null) 
			left join [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.ASBRService asbr on ab.service_id = asbr.Service_Id
			group by ab.FifteenMinutesInterval, ab.service_id, ab.service_c, ab.callstartdt, ab.SeqNum, ab.callid, ab.ani, ab.dnis, ab.SiteName, agt.agent_site, ab.callactionid, ab.param5, ab.[sin], ab.abandondelay, asbr.TargetQTime, ab.skillsetabandondelay, ab.handlingtime
		),
		
		handled_agent_site as (
			select hd.FifteenMinutesInterval,hd.callstartdt,hd.service_id,hd.service_c,hd.skill_id,hd.skill_desc,hd.seqnum,hd.callid,hd.callactionid,hd.ani,hd.dnis,hd.sitename,hd.station,isnull(agt.agent_site, 'MOC') as proper_site_tagging,
			count(distinct agt.User_Id) as number_of_online_agents,1 as answered_flag,0 as overflow_flag,hd.param5 as last_ivr,hd.[sin],1 as repeat_count,hd.answerdelay as [delay],hd.skillsetanswerdelay as [skillsetdelay],
			hd.handlingtime as [handlingtime],case when hd.answerdelay <= asbr.TargetQTime then 1 else 0 end as WinThreshold from handled_base hd
			left join agent_site_login agt on hd.[user_id] = agt.[User_Id] and (hd.callstartdt between agt.LoginDt and agt.LogoutDt or hd.callstartdt >= agt.LoginDt and agt.LogoutDt is null) 
			left join [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.ASBRService asbr on hd.service_id = asbr.Service_Id
			group by hd.FifteenMinutesInterval, hd.service_id, hd.service_c, hd.skill_id, hd.skill_desc, hd.callstartdt, hd.seqnum, hd.callid, hd.ani, hd.dnis, hd.station, hd.sitename, agt.agent_site, hd.callactionid, hd.param5, hd.[sin], hd.answerdelay, asbr.TargetQTime, hd.skillsetanswerdelay, hd.handlingtime --, hd.dnis, hd.SiteName, hd.queuestartdt, hd.queueenddt, hd.wrapenddt, hd.service_id
		),

		overflowed_agent_site as (
			select	ov.FifteenMinutesInterval,ov.callstartdt,ov.service_id,	ov.service_c,null as skill_id,null as skill_desc,ov.seqnum,ov.callid,ov.callactionid,ov.ani,ov.dnis,ov.sitename,'' as station,isnull(agt.agent_site, 'MOC') as proper_site_tagging,
			count(distinct agt.User_Id) as number_of_online_agents,null as answered_flag,1 as overflow_flag,ov.param5 as last_ivr,ov.[sin],0 as repeat_count,ov.overflowdelay as [delay],ov.skillsetoverflowdelay as [skillsetdelay],
			ov.handlingtime as [handlingtime],case when ov.overflowdelay <= asbr.TargetQTime then 1 else 0 end as WinThreshold
			from overflowed_base ov	left join agent_site_login agt on (ov.callstartdt between agt.LoginDt and agt.LogoutDt or ov.callstartdt >= agt.LoginDt and agt.LogoutDt is null) and ov.SiteName = agt.agent_site -- or (ab.callstartdt >= agt.LoginDt and agt.LogoutDt is null) 
			left join [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.ASBRService asbr on ov.service_id = asbr.Service_Id
			group by ov.FifteenMinutesInterval, ov.service_id, ov.service_c, ov.callstartdt, ov.SeqNum, ov.callid, ov.ani, ov.dnis, ov.SiteName, agt.agent_site, ov.callactionid, ov.param5, ov.[sin], ov.overflowdelay, asbr.TargetQTime, ov.skillsetoverflowdelay, ov.handlingtime
		),

		offered_calls as (select * from handled_agent_site union all select * from abandoned_agent_site union all	select * from overflowed_agent_site),

		flat_table as (
			select distinct	incoming.FifteenMinutesInterval as '15MinsInterval',DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 30,incoming.FifteenMinutesInterval)) / 30 * 30, 0)) as '30MinsInterval'
			,DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 60,incoming.FifteenMinutesInterval)) / 60 * 60, 0)) as '60MinsInterval'
			,CONVERT(VARCHAR(20), (dateadd(dd, 0 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,incoming.FifteenMinutesInterval))) %7 , dateadd(hour, 0,incoming.FifteenMinutesInterval))), 101) as Sunday
			,CONVERT(VARCHAR(20), (dateadd(dd, 6 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,incoming.FifteenMinutesInterval))) %7 , dateadd(hour, 0,incoming.FifteenMinutesInterval))), 101) as Saturday
			,convert(varchar(10), dateadd(hour, 0,incoming.FifteenMinutesInterval), 101) + ' ' +
				format(CAST(DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,incoming.FifteenMinutesInterval)) / 15 * 15, 0)) as Datetime), 'HH:mm:ss.fff') as Minus15Mins
			,convert(varchar(10), dateadd(hour, 0,incoming.FifteenMinutesInterval), 101) + ' ' +
				format(CAST(DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,incoming.FifteenMinutesInterval)) / 30 * 30, 0)) as Datetime), 'HH:mm:ss.fff') as Minus30Mins
			,convert(varchar(10), dateadd(hour, 0,incoming.FifteenMinutesInterval), 101) + ' ' +
				format(CAST(DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,incoming.FifteenMinutesInterval)) / 60 * 60, 0)) as Datetime), 'HH:mm:ss.fff') as Minus60Mins
			,datepart(wk, dateadd(hour, 0,incoming.FifteenMinutesInterval)) as Week_count,datepart(w, dateadd(hour, 0,incoming.FifteenMinutesInterval)) as Day_Count
			,datename(dw, dateadd(hour, 0,incoming.FifteenMinutesInterval)) as Day_Name,convert(varchar(10), dateadd(hour, 0,incoming.FifteenMinutesInterval), 101) as perDate
			,DATEPART(M, DATEADD(hour, 0,incoming.FifteenMinutesInterval)) AS Month_Count
			,cast(datename(m, dateadd(hour,0,incoming.FifteenMinutesInterval)) as varchar(10)) + ', ' + cast(year(dateadd(hour,0,incoming.FifteenMinutesInterval)) as varchar(10)) AS MONTH_NAME
			,cast(year(dateadd(hour,0,incoming.FifteenMinutesInterval)) as varchar(10)) + ', ' + cast(datename(m, dateadd(hour,0,incoming.FifteenMinutesInterval)) as varchar(10)) as Year_Month
			,YEAR(DATEADD(hour, 0,incoming.FifteenMinutesInterval)) AS YEAR,DATEADD(hour, 0,incoming.FifteenMinutesInterval) AS DATE_TIME
			,convert(varchar(10), dateadd(hour, 0,incoming.FifteenMinutesInterval), 101) +' '+
				case when datepart(hour, dateadd(hour, 0, incoming.FifteenMinutesInterval)) <= 5 or datepart(hour, dateadd(hour, 0, incoming.FifteenMinutesInterval)) >= 22 then '10PM - 6AM'
				when datepart(hour, dateadd(hour, 0, incoming.FifteenMinutesInterval)) > = 14 and datepart(hour, dateadd(hour, 0, incoming.FifteenMinutesInterval)) <= 21 then '2PM - 10PM'
				when datepart(hour, dateadd(hour, 0, incoming.FifteenMinutesInterval)) >= 6 and datepart(hour, dateadd(hour, 0, incoming.FifteenMinutesInterval)) <= 13 then '6AM - 2PM' end as Service_Shift
			,isnull(incoming.answered_flag, 0) as Answered,case when incoming.answered_flag = 0 then 1 else 0 end as Abandoned,isnull(incoming.overflow_flag, 0) as CallsOverFlowed
			,case when (isnull(incoming.answered_flag, 0) = 1) and (isnull(incoming.overflow_flag, 0) = 0) then isnull(incoming.delay, 0) else 0 end as AnsweredDelay
			,case when (isnull(incoming.answered_flag, 0) = 1) and (isnull(incoming.overflow_flag, 0) = 0) then isnull(incoming.skillsetdelay, 0) else 0 end as AnsDelayAtSkillset
			,(case when (isnull(incoming.answered_flag, 0) = 1) and (isnull(incoming.overflow_flag, 0) = 0) then (case when (isnull(incoming.WinThreshold, 0)) = 0 then 1 else 0 end) else 0 end) as AnsAfterThreshold
			,(case when (isnull(incoming.answered_flag, 0) = 1) and (isnull(incoming.overflow_flag, 0) = 0) then (case when (isnull(incoming.WinThreshold, 0)) = 1 then 1 else 0 end) else 0 end) as AnsWithinThreshold
			,isnull(incoming.handlingtime, 0) as HandlingTime
			,(case when (isnull(incoming.answered_flag, 0) = 0) and (isnull(incoming.overflow_flag, 0) = 0) then incoming.[delay] else 0 end) as AbandonDelay
			,(case when (isnull(incoming.answered_flag, 0) = 0) and (isnull(incoming.overflow_flag, 0) = 0) then (case when (incoming.[delay] > 20) then 1  else 0 end )else 0 end) as AbanAfterThreshold
			,incoming.service_c,incoming.service_id,incoming.skill_id,incoming.sitename as SiteName,incoming.seqnum,incoming.callactionid,incoming.callstartdt,incoming.ani,incoming.repeat_count
			,incoming.sin,incoming.skill_desc,incoming.last_ivr	from offered_calls incoming
		),

		partitioned_table as (
			SELECT *, ROW_NUMBER() OVER(PARTITION BY flat_table.ani, flat_table.service_id, flat_table.skill_id, (DATEADD(minute, (DATEDIFF(minute, '', flat_table.callstartdt)/1440)*1440, '')) , flat_table.skill_id ORDER BY flat_table.ani, flat_table.callstartdt) AS ani_instance
			FROM flat_table
		),

		updated_repeat_table as (select *,case when pt.ani_instance = 1 and pt.repeat_count > 0 then pt.repeat_count - pt.ani_instance else pt.repeat_count end as repeat_instance_count	from partitioned_table pt),

		final_query as (
			select	flat.[15MinsInterval],flat.[30MinsInterval],flat.[60MinsInterval],flat.Sunday,flat.Saturday,flat.Minus15Mins,flat.Minus30Mins,flat.Minus60Mins,flat.Week_count,flat.Day_Count,flat.Day_Name,flat.perDate,
			flat.Month_Count,flat.MONTH_NAME,flat.Year_Month,flat.Year,flat.DATE_TIME,flat.Service_Shift
			,isnull(flat.Skill_Id, 
							case flat.Service_Id
								when 4000013 then 4000019
								when 4000018 then 4000019
								when 4000019 then 4000023
								when 4000020 then 4000023
								when 4000027 then 4000050
								when 4000028 then 4000050
								when 4000029 then 4000051
								when 4000030 then 4000051
								when 4000021 then 4000044
								when 4000022 then 4000044
								when 4000023 then 4000049
								when 4000024 then 4000049
								when 4000025 then 4000048
								when 4000026 then 4000048
							end
		)as skill_id
		,isnull(flat.Skill_Desc, case flat.Service_Id
								when 4000013 then 'Res_Customer_Assist_sk'
								when 4000018 then 'Res_Customer_Assist_sk'
								when 4000019 then 'Biz_Customer_Assist_sk'
								when 4000020 then 'Biz_Customer_Assist_sk'
								when 4000027 then 'Cxe_Customer_Assist_sk'
								when 4000028 then 'Cxe_Customer_Assist_sk'
								when 4000029 then 'Dpa_Customer_Assist_sk'
								when 4000030 then 'Dpa_Customer_Assist_sk'
								when 4000021 then 'Pres_Customer_Assist_sk'
								when 4000022 then 'Pres_Customer_Assist_sk'
								when 4000025 then 'Gov_Customer_Assist_sk'
								when 4000026 then 'Gov_Customer_Assist_sk'
								when 4000024 then 'Kwatch_Customer_Assist_sk'
								when 4000023 then 'Kwatch_Customer_Assist_sk'
								
							end
							) as Skill_Desc
			,flat.sin as 'SIN',flat.ani as ContactNumber,Sum(flat.repeat_instance_count) as RepeatCalls,Max(flat.last_ivr) as LastIVRTransaction,Dateadd(hour, 8, Max(flat.callstartdt)) as  LastDateTimeCall
			,(Sum(flat.Answered) + Sum(flat.Abandoned)) as 'Total Calls',flat.service_c as Service_c,flat.service_id,flat.SiteName,0 as ANICount from updated_repeat_table flat 
			group by flat.[15MinsInterval],flat.[30MinsInterval],flat.[60MinsInterval],flat.Sunday,flat.Saturday,flat.Minus15Mins,flat.Minus30Mins,flat.Minus60Mins,flat.Week_count,flat.Day_Count,
			flat.Day_Name,flat.perDate,flat.Month_Count,flat.MONTH_NAME,flat.Year_Month,flat.YEAR,flat.DATE_TIME,flat.service_id,flat.service_c,flat.SiteName,flat.Service_Shift,flat.skill_id,flat.skill_desc,flat.sin,flat.ani
		)

		select * from final_query where service_id in (" . $svd . ") and  skill_id in (" . $skd . ") and SiteName IN (" . $sid . ") order by \"15MinsInterval\",\"LastDateTimeCall\"";
		$query = $this->db->query($cmd);	   
		return $query;
	}
	
	public function get_report_07($datefrom,$dateto,$site){
		$site = rtrim($site,",");
		$sites = explode(",",$site);
		$sid = "";
		foreach($sites as $sitelist){
			$sid .= "'".$sitelist."',";
		}
		$sid =  rtrim($sid,",");
		$cmd = "DECLARE @FromDateTime datetime = '".$datefrom."', @ToDateTime datetime = '".$dateto."';

		with base as (
			SELECT DISTINCT	DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 15, DATEADD(HOUR, 8, cb.Callback_Dt))) / 15 * 15, 0) as Interval,
			DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 15, cb.Callback_Dt)) / 15 * 15, 0)) as \"15MinsInterval\",
			DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 30, cb.Callback_Dt)) / 30 * 30, 0)) as \"30MinsInterval\",
			DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 60, cb.Callback_Dt)) / 60 * 60, 0)) as \"60MinsInterval\",
			CONVERT(VARCHAR(20), (dateadd(dd, 0 - (@@datefirst +6 + datepart(dw, dateadd(hour, 8,cb.Callback_Dt))) %7 , dateadd(hour, 8,cb.Callback_Dt))), 101) as Sunday,
			CONVERT(VARCHAR(20), (dateadd(dd, 6 - (@@datefirst +6 + datepart(dw, dateadd(hour, 8,cb.Callback_Dt))) %7 , dateadd(hour, 8,cb.Callback_Dt))), 101) as Saturday,
			convert(varchar(10), dateadd(hour, 8,cb.Callback_Dt), 101) + ' ' +
			format(CAST(DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, cb.Callback_Dt)) / 15 * 15, 0)) as Datetime), 'hh:mm tt') as Minus15Mins,
			convert(varchar(10), dateadd(hour, 8,cb.Callback_Dt), 101) + ' ' +
			format(CAST(DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, cb.Callback_Dt)) / 30 * 30, 0)) as Datetime), 'hh:mm tt') as Minus30Mins,
			convert(varchar(10), dateadd(hour, 8,cb.Callback_Dt), 101) + ' ' +
			format(CAST(DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, cb.Callback_Dt)) / 60 * 60, 0)) as Datetime), 'hh:mm tt') as Minus60Mins,
			datepart(wk, dateadd(hour, 8, cb.Callback_Dt)) as Week_count,datepart(w, dateadd(hour, 8, cb.Callback_Dt)) as Day_Count, datename(dw, dateadd(hour, 8, cb.Callback_Dt)) as Day_Name,
			convert(varchar(10), dateadd(hour, 8,cb.Callback_Dt), 101) as perDate, DATEPART(M, DATEADD(HOUR, 8, cb.Callback_Dt)) AS Month_Count,
			cast(datename(m, dateadd(hour,8,cb.Callback_Dt)) as varchar(10)) + ', ' + cast(year(dateadd(hour,8,cb.Callback_Dt)) as varchar(10)) AS MONTH_NAME,
			cast(year(dateadd(hour,8,cb.Callback_Dt)) as varchar(10)) + ', ' + cast(datename(m, dateadd(hour,8,cb.Callback_Dt)) as varchar(10)) as Year_Month,
			YEAR(DATEADD(HOUR, 8, cb.Callback_Dt)) AS YEAR,	DATEADD(HOUR, 8, cb.Callback_Dt) AS DATE_TIME, convert(varchar(10), dateadd(hour, 8,cb.Callback_Dt), 101) +' '+
			case when datepart(hour, dateadd(hour, 8, cb.Callback_Dt)) <= 5 or datepart(hour, dateadd(hour, 8, cb.Callback_Dt)) >= 22 then '10PM - 6AM' end as Graveyard,
			convert(varchar(10), dateadd(hour, 8,cb.Callback_Dt), 101) +' '+
			case when datepart(hour, dateadd(hour, 8, cb.Callback_Dt)) > = 14 and datepart(hour, dateadd(hour, 8, cb.Callback_Dt)) <= 21 then '2PM - 10PM'  end as Afternoon,
			convert(varchar(10), dateadd(hour, 8,cb.Callback_Dt), 101) +' '+
			 case when datepart(hour, dateadd(hour, 8, cb.Callback_Dt)) >= 6 and datepart(hour, dateadd(hour, 8, cb.Callback_Dt)) <= 13 then '6AM - 2PM' end as Morning,
			cb.Service_id as service_id1, cb.Table_id as table_id1,	cb.Record_Num, cbf.Param1 as SIN,dateadd(hour,8,cb.Callback_Dt) as Call1_dt,substring(cbf.Param6,0,12) as [DateOfRequest],substring(cbf.Param6,12,8) as [TimeOfRequest],
			cb.checkout_f,cbf.Param2 as Landline,cbf.Param5 as ConcernType,LTRIM(RTRIM((SELECT SUBSTRING(SUBSTRING(Memo,CHARINDEX('< ',Memo)+1,LEN(Memo)),0,CHARINDEX(' >',SUBSTRING(Memo,CHARINDEX('< ',Memo)+1,LEN(Memo)))))))  as SeqNum1
			 FROM [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[CallBack] cb LEFT JOIN [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[CallBackFields] cbf
			 ON cb.Table_Id = cbf.Table_Id and cb.service_id = cbf.service_id and cb.callback_dt = cbf.callback_dt and cb.record_num = cbf.record_num
			 WHERE cb.service_id in (4000002) AND dateadd(hour,8,cb.Callback_Dt) between @FromDateTime and @ToDateTime
			 group by cb.service_id,cb.Table_id,cb.Record_Num,cb.Callback_Dt,cbf.Param6,cbf.Param2,cbf.Param5,cb.checkout_f,Memo,cbf.Param1 
			)

		, final_query as (
		select a.[15MinsInterval],a.[30MinsInterval],a.[60MinsInterval],a.Minus15Mins,a.Minus30Mins,a.Minus60Mins,a.Sunday,a.Saturday,a.perDate,a.DATE_TIME,a.MONTH_NAME,a.Morning,a.Afternoon,a.Graveyard,a.YEAR,a.SeqNum1 as SeqNum,a.service_id1 as Service_Id,a.Table_id1 as Table_Id,a.Record_Num,SIN,a.Call1_dt,a.DateOfRequest,a.TimeOfRequest,a.Landline as LandlineNumber,a.ConcernType,checkout_f,DATEADD(HOUR, 8, aod.callstartdt)as \"CallStartDt\",
		CASE WHEN AOD.[AgentDispId] IS NOT NULL THEN AOD.[AgentDispId] ELSE AOD.[SwitchDispId] END as [DispositionId],
		(SELECT [Disposition_desc] FROM [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Disposition] WHERE [Disp_Id] = CASE WHEN AOD.[AgentDispId] IS NOT NULL THEN AOD.[AgentDispId] ELSE AOD.[SwitchDispId] END) as [Disposition_desc]
		,isnull(sites.SiteName,'MOC') as SiteName from base a left join [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[AODCalldetail] aod
		on a.SeqNum1 = aod.seqnum left join [RepUIDB].dbo.Stations station on station.Station = aod.station	left join [RepUIDB].dbo.Sites sites on station.SiteGuid =  sites.SiteGuid
		)

		select  * from final_query where SiteName IN (".$sid.") and (SeqNum like ('1%') or SeqNum like ('2%') or SeqNum like ('3%')	or SeqNum like ('4%') or SeqNum like ('5%') or SeqNum like ('6%') or SeqNum like ('7%')
		or SeqNum like ('8%') or SeqNum like ('9%') or SeqNum like ('0%')) order by Call1_dt";
		$query = $this->db->query($cmd);	   
		return $query;
	}
	
	public function get_report_14($datefrom ,$dateto){
		if(date('h:i:s A',strtotime($dateto)) == "11:59:59 PM") {
			$dateto = date('m/d/Y h:i:s A',strtotime($dateto ." +1 second"));
		}
		$cmd = "DECLARE @FromDateTime datetime;	DECLARE @ToDateTime datetime;set @FromDateTime='".$datefrom."';	set @ToDateTime='".$dateto."';
		SELECT _IVRSubTable.AssignedApplication,(convert(Decimal(10,2),  case when _IVRSubTable.TargetTimeAvailable=0 then 1 else _IVRSubTable.TargetTimeAvailable/60 end  )) as 'Target Time Available (minutes)'
		   ,(convert(Decimal(10,2),case when IVRAvailability.ActualTimeAvailable=0 then 1 else IVRAvailability.ActualTimeAvailable end /60)) as 'Actual Time Available (minutes)'
			,convert(varchar(10), convert(Decimal(10,2), (convert(Decimal(10,2),case when IVRAvailability.ActualTimeAvailable=0 then 0 else IVRAvailability.ActualTimeAvailable/60 end  ))/(convert(Decimal(10,2),case when _IVRSubTable.TargetTimeAvailable=0 then 1 else _IVRSubTable.TargetTimeAvailable/60 end)) * 100 )) + ' %' as '% Availability' 
			,_IVRSubTable.LastDateTimeTransaction,IVRAvailability.DownTime
			FROM (
		SELECT DISTINCT _IVRHotlineSubTable.[ID],_IVRHotlineSubTable.[AssignedApplication],_IVRHotlineSubTable.[TargetTimeAvailable],_IVRLastDateTransaction.[LastDateTimeTransaction]
		FROM (SELECT _baseIVRHotlineTable.[ID],_baseIVRHotlineTable.[Hotlines] AS  AssignedApplication,_baseIVRHotlineTable.[AssociatedDNIS],(DATEDIFF(\"s\", @FromDateTime, @ToDateTime)) AS TargetTimeAvailable
			FROM (SELECT * FROM [vw12pcticxpdb01].[TransactionDB].[dbo].[TblHotlineNumbers]) _baseIVRHotlineTable) _IVRHotlineSubTable
			LEFT JOIN (SELECT DISTINCT IVRTransaction.[DNIS],(MAX(CONVERT(VARCHAR(50), IVRTransaction.[CallDate],131))) AS LastDateTimeTransaction
					FROM [vw12pcticxpdb01].[TransactionDB].[dbo].[IVRSMenu] IVRTransaction WHERE IVRTransaction.[DNIS] IN 
						('9501','9502','9504','9505','9506','9507','9508','52529501','52529502','52529504','52529505','52529506','52529507','52529508') AND
						(CONVERT(VARCHAR(50), IVRTransaction.[CallDate],131) >= @FromDateTime AND CONVERT(VARCHAR(50), IVRTransaction.[CallDate],131) <= @ToDateTime) GROUP BY IVRTransaction.[DNIS]) _IVRLastDateTransaction
		ON _IVRLastDateTransaction.[DNIS] = _IVRHotlineSubTable.[AssociatedDNIS]
		WHERE _IVRHotlineSubTable.[AssociatedDNIS] IN ('9501','9502','9504','9505','9506','9507','9508','52529501','52529502','52529504','52529505','52529506','52529507','52529508')) _IVRSubTable,
		(SELECT	(SUM(DATEDIFF(\"s\",((CASE WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) > @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) < @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131)
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) < @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) < @ToDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) > @FromDateTime THEN @FromDateTime
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) < @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) > @ToDateTime AND CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) < @ToDateTime THEN @FromDateTime
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) > @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) > @ToDateTime AND CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) > @ToDateTime THEN @ToDateTime
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) > @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) > @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131)
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) = @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) = @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131)
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) = @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) < @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131)
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) = @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) > @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131)
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) > @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) = @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131)
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) < @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) = @ToDateTime THEN @FromDateTime
			END) ) 
			,((CASE WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) > @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) < @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.NMSDateTime),131)
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) < @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) < @ToDateTime AND CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) < @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.NMSDateTime),131)
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) < @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) > @ToDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) > @FromDateTime THEN @ToDateTime
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) > @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) > @ToDateTime AND CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) > @ToDateTime THEN @ToDateTime
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) > @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) > @ToDateTime THEN @ToDateTime
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) = @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) = @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.NMSDateTime),131)
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) = @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) < @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.NMSDateTime),131)
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) = @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) > @ToDateTime THEN @ToDateTime
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) > @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) = @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.NMSDateTime),131)
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) < @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) = @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.NMSDateTime),131)
			END) ) ))
		) AS DownTime
		,((DATEDIFF(\"s\", @FromDateTime, @ToDateTime)) - (SUM(DATEDIFF(\"s\",
				(CASE WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) > @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) < @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131)
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) < @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) < @ToDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) > @FromDateTime THEN @FromDateTime
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) < @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) > @ToDateTime AND CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) < @ToDateTime THEN @FromDateTime
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) > @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) > @ToDateTime AND CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) > @ToDateTime THEN @ToDateTime
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) > @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) > @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131)
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) = @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) = @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131)
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) = @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) < @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131)
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) = @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) > @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131)
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) > @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) = @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131)
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) < @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) = @ToDateTime THEN @FromDateTime END )
	  
				,(CASE WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) > @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) < @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.NMSDateTime),131)
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) < @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) < @ToDateTime AND CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) < @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.NMSDateTime),131)
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) < @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) > @ToDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) > @FromDateTime THEN @ToDateTime
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) > @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) > @ToDateTime AND CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) > @ToDateTime THEN @ToDateTime
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) > @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) > @ToDateTime THEN @ToDateTime
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) = @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) = @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.NMSDateTime),131)
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) = @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) < @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.NMSDateTime),131)
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) = @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) > @ToDateTime THEN @ToDateTime
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) > @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) = @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.NMSDateTime),131)
					WHEN CONVERT(VARCHAR(50), (SMT.SMSDateTime),131) < @FromDateTime AND CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) = @ToDateTime THEN CONVERT(VARCHAR(50), (SMT.NMSDateTime),131) END ))))
		) AS ActualTimeAvailable FROM [vw12pcticxpdb01].[IVRSDB].[dbo].[TblServiceModeTransaction] SMT) IVRAvailability";
		$query = $this->db->query($cmd);
		return $query;
	}
	
	public function get_report_17($datefrom ,$dateto){
	
				$cmd = "Select CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')) as ConvertInterval ,
			DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 15, DATEADD(HOUR, 0, CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000'))))) / 15 * 15, 0) as Interval,
			DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')))) / 15 * 15, 0)) as '15MinsInterval',
			DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')))) / 30 * 30, 0)) as '30MinsInterval',
			DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')))) / 60 * 60, 0)) as '60MinsInterval',
			CONVERT(VARCHAR(20), (dateadd(dd, 0 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000'))))) %7 , dateadd(hour, 0,CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000'))))), 101) as Sunday,
			CONVERT(VARCHAR(20), (dateadd(dd, 6 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000'))))) %7 , dateadd(hour, 0,CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000'))))), 101) as Saturday,
			convert(varchar(10), dateadd(hour, 0,CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000'))), 101) + ' ' +
			format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')))) / 15 * 15, 0)) as Datetime), 'hh:mm tt') as Minus15Mins,
			convert(varchar(10), dateadd(hour, 0,CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000'))), 101) + ' ' +
			format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')))) / 30 * 30, 0)) as Datetime), 'hh:mm tt') as Minus30Mins,
			convert(varchar(10), dateadd(hour, 0,CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000'))), 101) + ' ' +
			format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')))) / 60 * 60, 0)) as Datetime), 'hh:mm tt') as Minus60Mins,
			datepart(wk, dateadd(hour, 0, CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')))) as Week_count,
			datepart(w, dateadd(hour, 0, CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')))) as Day_Count, 
			datename(dw, dateadd(hour, 0, CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')))) as Day_Name,
			convert(varchar(10), dateadd(hour, 0,CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000'))), 101) as perDate,
			DATEPART(M, DATEADD(HOUR, 0, CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')))) AS Month_Count,
			cast(datename(m, dateadd(hour,0,CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')))) as varchar(10)) + ', ' + cast(year(dateadd(hour,0,CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')))) as varchar(10)) AS MONTH_NAME,
			cast(year(dateadd(hour,0,CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')))) as varchar(10)) + ', ' + cast(datename(m, dateadd(hour,0,CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')))) as varchar(10)) as Year_Month,
			YEAR(DATEADD(HOUR, 0, CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')))) AS YEAR,
			DATEADD(HOUR, 0, CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000'))) AS DATE_TIME,
			case when datepart(hour, dateadd(hour, 0, CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')))) <= 5 then convert(varchar(10), dateadd(DAY, -1,dateadd(hour, 0,CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')))), 101) 
			else convert(varchar(10), dateadd(hour, 0,CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000'))), 101) end + ' ' +
			case when datepart(hour, dateadd(hour, 0, CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')))) <= 5 or datepart(hour, dateadd(hour, 0, CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')))) >= 22 then '10PM - 6AM'
			when datepart(hour, dateadd(hour, 0, CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')))) > = 14 and datepart(hour, dateadd(hour, 0, CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')))) <= 21 then '2PM - 10PM'
			when datepart(hour, dateadd(hour, 0, CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')))) >= 6 and datepart(hour, dateadd(hour, 0, CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')))) <= 13 then '6AM - 2PM' end
			as Service_Shift,a.AdhocEventID, b.hotline, b.AnncID, b.StartDate, b.EndDate , b.StartTime, b.EndTime,(SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00') as st_time,
			(SUBSTRING(b.endTime, 1, 2) + ':' + SUBSTRING(b.endTime, 3, 4)+ ':' + '00') as dt_time,Convert(varchar,floor(datediff(second, convert(datetime,b.StartDate + ' ' + left(b.StartTime,2) + ':' + right(b.StartTime,2) +':00'),
			convert(datetime,b.StartDate + ' ' + left(b.EndTime,2) + ':' + right(b.EndTime,2) +':00'))/86400)) + ' day/s ' + ' ' + Convert(varchar,floor(datediff(second, convert(datetime,b.StartDate + ' ' + left(b.StartTime,2) + ':' + right(b.StartTime,2) +':00'),
			convert(datetime,b.StartDate + ' ' + left(b.EndTime,2) + ':' + right(b.EndTime,2) +':00'))/3600)) + ' hour/s ' + ' ' + Convert(varchar,format(dateadd(second,datediff(second, convert(datetime,b.StartDate + ' ' + left(b.StartTime,2) + ':' + right(b.StartTime,2) +':00'),
			convert(datetime,b.StartDate + ' ' + left(b.EndTime,2) + ':' + right(b.EndTime,2) +':00')),'00:00:00'),N'mm')) + ' minute/s' as Duration ,
			Sum(isnull(cast(b.DiscAfterAnn as float),0)) AS DiscAfterAnn, Sum(isnull(cast(B.TotalCalls as float),0)) AS TotalCalls, 
			DATEDIFF(\"S\",CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')),
			CONVERT(DATETIME,(b.EndDate + ' ' + SUBSTRING(b.EndTime, 1, 2) + ':' + SUBSTRING(b.EndTime, 3, 4)+ ':' + '00.000'))) as SubDuration,
			Sum(isnull(cast(b.DiscAfterAnn as float),0)) / Sum(isnull(cast(B.TotalCalls as float),0)) AS [% Disconnected Calls] ,Count(Distinct(c.[AdhocActivated])) as AnnsActivated
			FROM 
			[VW12PCTICXPDB01].[TransactionDB].[dbo].[tblAdhocActivationAuditLogs] a, [VW12PCTICXPDB01].[TransactionDB].[dbo].[TblADHOCTransaction] b, [VW12PCTICXPDB01].[TransactionDB].[dbo].[tblAdhocActivationAuditLogs]  c With(NOLOCK) 
			WHERE a.AdhocEventID = c.AdhocEventId and c.[AdhocActivated] = 'Y' and  a.AdhocEventID = b.LastEventAdhocId and CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')) >= '".$datefrom."'
			AND CONVERT(DATETIME,(b.StartDate + ' ' + SUBSTRING(b.StartTime, 1, 2) + ':' + SUBSTRING(b.StartTime, 3, 4)+ ':' + '00.000')) <= '".$dateto."'
			GROUP BY a.AdhocEventID, b.hotline, b.AnncID, b.StartDate, b.EndDate , b.StartTime, b.EndTime, c.[AdhocActivated]  order by ConvertInterval ASC";
		
			$query = $this->db->query($cmd);
			return $query;
	}
	
	public function get_report_4($datefrom ,$dateto){
		$tblname = "vhis4_". date('mdYhis'). '_' . str_replace('-','',$this->session->userdata("Token")) ."";
		$cmd = "
			BEGIN 
			SET NOCOUNT ON;
			DECLARE @tempCradleToGrave TABLE ([UMID] NVARCHAR(50) NULL,[SeqNum] NVARCHAR(20) NULL,[ServiceID] nvarchar(20) NULL,[CallTypeID] int,[CallDateTime] datetime NULL,[CallActionId] int NULL,
			[CallActionDesc] varchar(200) NULL,[CallActionReasonId] int NULL,[CallActionReasonDesc] varchar(200) NULL,[CallDate] nvarchar(20) NULL,[CallTime] nvarchar(20) NULL,[EventID] float,
			[Event] nvarchar(200) NULL,Agent nvarchar(200) NULL,[Source] nvarchar(200) NULL,Destination nvarchar(200) NULL,CallData nvarchar(MAX) NULL,[EventData] nvarchar(200) NULL,[rn] float);

			DECLARE @FromDateTime DATETIME;
			DECLARE @ToDateTime DATETIME;
			SET @FromDateTime = '".$datefrom."';
			SET @ToDateTime = '".$dateto."';

			INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallDateTime, CallDate, CallTime, [Event], EventID, Agent, [Source], Destination, CallData, [EventData])
			SELECT RIGHT(DLG_UMID,22) AS UMID, NULL AS SeqNum, NULL AS ServiceID, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,UTC_TIME),'+08:00')) AS CallDateTime
			, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,UTC_TIME),'+08:00')),101) AS CallDate
			, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,UTC_TIME),'+08:00')),108) AS CallTime
			, 'CXP Inbound Call' AS [Event]	, 0.1 as EventID, NULL AS Agent	, DLG_DNIS as [Source], NULL AS Destination	, 'ANI: ' + DLG_ANI AS CallData	, last_ds_name AS [EventData] 
			FROM [VW12PCTICXPDB01].[LOGDB].[voadmin].[VOLDDLGSTS] WHERE NOT(DLG_UMID IS NULL) AND CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,UTC_TIME),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime
			INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallDateTime, CallDate, CallTime, [Event], EventID, Agent, [Source], Destination, CallData, [EventData])
			SELECT  RIGHT(UMID,22) as UMID, NULL as SeqNum, NULL as ServiceID, CallDate as CallDateTime	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
			, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime	, 'Routed to CXP IVR' as [Event], 1.0 AS EventID, NULL as Agent	, 'Cisco' as [Source], 'DPA Agreement' as Destination
			, 'DNIS: ' + DNIS as CallData, 'DPAAgreement:' + DPAAgreement  as [EventData] FROM  [VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
			WHERE [Language] <> '' AND [Language] NOT IN ('Abandoned', 'Terminated', 'Transfer') AND CallDate BETWEEN @FromDateTime AND @ToDateTime
			UNION SELECT  RIGHT(UMID,22) as UMID, NULL as SeqNum, NULL as ServiceID, CallDate as CallDateTime, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
			, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime, 'Routed to CXP IVR' as [Event], 1.1 AS EventID	, NULL as Agent	, 'DPA Agreement' as [Source]
			, 'Language' as Destination	, 'DNIS: ' + DNIS as CallData, 'Language: ' + [Language]  as [EventData]
			FROM  [VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu] WHERE [Language] <> '' AND [Language] NOT IN ('Abandoned', 'Terminated', 'Transfer') AND CallDate BETWEEN @FromDateTime AND @ToDateTime
			UNION SELECT  RIGHT(UMID,22) as UMID, NULL as SeqNum, NULL as ServiceID	, CallDate as CallDateTime, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
			, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime, 'IVR Script' as [Event], 1.12 AS EventID, NULL as Agent, 'Language' as [Source]	, 'Main Menu' as Destination
			, 'DNIS: ' + DNIS as CallData, 'Main Menu: ' + MainMenu  as [EventData]	FROM  [VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
			WHERE [Language] <> '' AND [Language] NOT IN ('Abandoned', 'Terminated', 'Transfer') AND CallDate BETWEEN @FromDateTime AND @ToDateTime

			UNION
			SELECT  RIGHT(UMID,22) as UMID, NULL as SeqNum, NULL as ServiceID, CallDate as CallDateTime	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
				, 'IVR Script' as [Event], 1.3 AS EventID, NULL as Agent, 'OutagesAndIncidents' as [Source]	, OutagesAndIncidents as Destination, 'DNIS: ' + DNIS as CallData, NULL as [EventData]
			FROM [VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]	WHERE [OutagesAndIncidents] <> '' AND [OutagesAndIncidents] NOT IN ('Abandoned', 'Terminated', 'Transfer') AND CallDate BETWEEN @FromDateTime AND @ToDateTime
			UNION
			SELECT RIGHT(UMID,22) as UMID, NULL as SeqNum, NULL as ServiceID, CallDate as CallDateTime, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
				, 'IVR Script' as [Event], 1.4 AS EventID, NULL as Agent, 'BillingAndPayments' as [Source], BillingAndPayments as Destination, 'DNIS: ' + DNIS as CallData, NULL as [EventData] 
			FROM [VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]	WHERE [BillingAndPayments] <> '' AND [BillingAndPayments] NOT IN ('Abandoned', 'Terminated', 'Transfer') AND CallDate BETWEEN @FromDateTime AND @ToDateTime
			UNION
			SELECT  RIGHT(UMID,22) as UMID, NULL as SeqNum, NULL as ServiceID, CallDate as CallDateTime, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
				, 'IVR Script' as [Event], 1.5 AS EventID, NULL as Agent, 'ProductsServicesAndPrograms' as [Source], ProductsServicesAndPrograms as Destination, 'DNIS: ' + DNIS as CallData, NULL as [EventData] 
			FROM [VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]	WHERE [ProductsServicesAndPrograms] <> ''  AND [ProductsServicesAndPrograms] NOT IN ('Abandoned', 'Terminated', 'Transfer')	AND CallDate BETWEEN @FromDateTime AND @ToDateTime
			UNION
			SELECT RIGHT(UMID,22) as UMID, NULL as SeqNum, NULL as ServiceID, CallDate as CallDateTime, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
				, 'IVR Script' As [Event], 1.6 AS EventID, NULL as Agent, 'Others' as [Source], Others as Destination, 'DNIS: ' + DNIS as CallData, NULL as [EventData] 
				FROM [VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu] WHERE [Others] <> '' AND [Others] NOT IN ('Abandoned', 'Terminated', 'Transfer') AND CallDate BETWEEN @FromDateTime AND @ToDateTime
			UNION
			SELECT  RIGHT(UMID,22) as UMID, NULL as SeqNum, NULL as ServiceID, CallDate as CallDateTime, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
				,'IVR Script' as [Event], 1.7 AS EventID, NULL as Agent, 'Other Menu' as [Source], OtherMenus as Destination, 'DNIS: ' + DNIS as CallData, NULL as [EventData]
				FROM [VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]	WHERE [OtherMenus] <> '' AND [OtherMenus] NOT IN ('Abandoned', 'Terminated', 'Transfer') AND CallDate BETWEEN @FromDateTime AND @ToDateTime
			UNION
			SELECT RIGHT(UMID,22) as UMID, NULL as SeqNum, NULL as ServiceID, CallDate as CallDateTime, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
				, 'IVR Script' As [Event], 2.1 AS EventID, NULL as Agent, 'Language' as [Source],[Language] as Destination, 'DNIS: ' + DNIS as CallData, NULL as [EventData] 
				FROM [VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]	WHERE [Language] IN  ('Abandoned', 'Terminated', 'Transfer') AND CallDate BETWEEN @FromDateTime AND @ToDateTime
			UNION
			SELECT  RIGHT(UMID,22) as UMID, NULL as SeqNum, NULL as Agent, CallDate as CallDateTime, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
				, 'IVR Script' As [Event], 2.2 AS EventID, NULL as Agent, 'Main Menu' as [Source], [MainMenu] as Destination, 'DNIS: ' + DNIS as CallData, NULL as [EventData]
				FROM [VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]	WHERE [MainMenu] IN  ('Abandoned', 'Terminated', 'Transfer') AND CallDate BETWEEN @FromDateTime AND @ToDateTime
			UNION
			SELECT  RIGHT(UMID,22) as UMID, NULL as SeqNum, NULL as ServiceID, CallDate as CallDateTime, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
				, 'IVR Script' As [Event], 2.3 AS EventID, NULL as Agent, 'OutagesAndIncidents' as [Source],[OutagesAndIncidents] as Destination, 'DNIS: ' + DNIS as CallData, NULL as [EventData]
			FROM [VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]	WHERE [OutagesAndIncidents] IN  ('Abandoned', 'Terminated', 'Transfer')	AND CallDate BETWEEN @FromDateTime AND @ToDateTime
			UNION
			SELECT  RIGHT(UMID,22) as UMID, NULL as SeqNum, NULL as ServiceID, CallDate as CallDateTime, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
				, 'IVR Script' As [Event], 2.4 AS EventID, NULL as Agent, 'BillingAndPayments' as [Source], BillingAndPayments as Destination, 'DNIS: ' + DNIS as CallData, NULL as [EventData]
			FROM [VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]	WHERE BillingAndPayments IN  ('Abandoned', 'Terminated', 'Transfer') AND CallDate BETWEEN @FromDateTime AND @ToDateTime
			UNION
			SELECT  RIGHT(UMID,22) as UMID, NULL as SeqNum, NULL as ServiceID, CallDate as CallDateTime, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
				, 'IVR Script' As [Event], 2.5 AS EventID, NULL as Agent, 'ProductsServicesAndPrograms' as [Source], ProductsServicesAndPrograms as Destination, 'DNIS: ' + DNIS as CallData, NULL as [EventData]
			FROM [VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]	WHERE ProductsServicesAndPrograms IN  ('Abandoned', 'Terminated', 'Transfer') AND CallDate BETWEEN @FromDateTime AND @ToDateTime
			UNION
			SELECT  RIGHT(UMID,22) as UMID, NULL as SeqNum, NULL as ServiceID,CallDate as CallDateTime, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
				, 'IVR Script' As [Event], 2.6 AS EventID, NULL as Agent, 'Others' as [Source], Others as Destination, 'DNIS: ' + DNIS as CallData, NULL as [EventData]
				FROM [VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]	WHERE Others IN  ('Abandoned', 'Terminated', 'Transfer') AND CallDate BETWEEN @FromDateTime AND @ToDateTime
			UNION
			SELECT  RIGHT(UMID,22) as UMID, NULL as SeqNum, NULL as ServiceID, CallDate as CallDateTime, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
				, 'IVR Script' As [Event], 2.7 AS EventID, NULL as Agent, 'OtherMenus' as [Source], OtherMenus as Destination, 'DNIS: ' + DNIS as CallData, NULL as [EventData]
			FROM [VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]	WHERE OtherMenus IN  ('Abandoned', 'Terminated', 'Transfer') AND CallDate BETWEEN @FromDateTime AND @ToDateTime;

			;with CTE as (
			SELECT  NULL as UMID, IVRN.SeqNum as SeqNum, IVRN.Service_Id as ServiceID, IVRN.NodeTypeId as CallTypeId, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,IVRN.CallStartDt),'+08:00')) as CallDateTime
				, IVRN.NodeActionID as CallActionID 
				, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,IVRN.CallStartDt),'+08:00')),101) as CallDate
				, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,IVRN.CallStartDt),'+08:00')),108) as CallTime
				, NA.NodeActionDesc as [Event], 2.8 as EventID, NULL as Agent, NULL as [Source], NULL as Destination, NULL as CallData, IVRO.Caption as EventData,ROW_NUMBER()OVER(Order by ivrn.seqnum) as rn
				, ivro.caption as caption FROM [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[IVRNodeActivityDetail] IVRN
			 left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[IVRScriptObjects] IVRO  on IVRN.ScriptID=IVRO.ScriptId and IVRN.ObjectId=IVRO.ObjectId 
			 left join [VW12PCTIDB01\UIP_CONFIG].[lookup].[dbo].[tlkpNodeActions] as NA on IVRN.NodeActionID=NA.NodeActionId
			WHERE CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,IVRN.CallStartDt),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime	)

			INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallTypeId, CallDateTime, CallActionId, CallDate, CallTime, [Event], EventID, Agent, [Source], Destination, CallData, [EventData], rn)
			select distinct base.umid, base.SeqNum, base.serviceID, base.CallTypeId, base.CallDateTime, base.CallActionID,
			 base.CallDate, base.CallTime, base.Event, base.EventID, base.Agent, base.caption as [Source], nxt.caption as Destination, NULL as CallData, Null as [EventData], base.rn 
			from cte base left join cte nxt on nxt.rn=base.rn+1 and nxt.SeqNum=base.SeqNum 

			;with CTE1 as (
			SELECT RIGHT(A.UMID,22) as UMID, A.SeqNum, A.Service_Id, A.CallTypeId, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')) as CallDateTime, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')),101) as CallDate
				, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')),108) as CallTime, B.CallTypeDesc as [Event], 3.1 as EventID, A.User_id as Agent
				, C.Service_c as [Source], NULL as Destination, 'ANI:' + A.ANI as CallData, NULL as [EventData], ROW_NUMBER()OVER(Order by A.seqnum, a.calltypeid desc) as rn, C.Service_c as caption
			FROM [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[CallDetail] as A INNER JOIN [VW12PCTIDB01\UIP_CONFIG].lookup.dbo.tlkpCallType As B ON A.CallTypeId = B.CallTypeId
			INNER JOIN [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Service As C ON A.Service_Id = C.Service_Id WHERE A.CallCategoryId = 1 and A.Service_id <> 4000085
			and CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime )

			INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallTypeId, CallDateTime, CallDate, CallTime, [Event], EventID	, Agent, [Source], Destination, CallData, [EventData], rn)
			select distinct base1.UMID, base1.SeqNum, base1.Service_Id, base1.CallTypeId, base1.CallDateTime,
			 base1.CallDate, base1.CallTime, base1.Event, base1.EventID, base1.Agent, base1.Source as [Source], nxt1.caption as Destination, NULL as CallData, Null as [EventData], base1.rn 
			from cte1 base1	left join cte1 nxt1 on nxt1.rn=base1.rn+1 and nxt1.SeqNum=base1.SeqNum order by base1.SeqNum, base1.CallDateTime, base1.rn

			UPDATE A SET A.CallActionId = B.CallActionId,A.CallActionReasonId = B.CallActionId,A.CallActionDesc = C.CallActionDesc, 
			A.CallActionReasonDesc = case B.CallActionId when 5 then 'Abandon Delay: ' when 6 then 'Abandon Delay: ' when 23 then 'Abandon Delay: ' when 24 then 'Abandon Delay: ' 
										else 'Queue Time: '
									 end + CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,B.QueueEndDt),'+08:00'))-	CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,B.QueueStartDt),'+08:00')),108) 
			FROM @tempCradleToGrave AS A INNER JOIN [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[ACDCallDetail] AS B ON B.SeqNum = A.Seqnum AND B.CallTypeId = A.CallTypeId 
				INNER JOIN [VW12PCTIDB01\UIP_CONFIG].[lookup].[dbo].[tlkpCallAction] AS C ON C.CallActionId = B.CallActionId
				INNER JOIN [VW12PCTIDB01\UIP_CONFIG].[lookup].[dbo].[tlkpCallActionReason] AS D ON D.CallActionReasonId = B.CallActionReasonId	where a.EventID =3.1 

			UPDATE @tempCradleToGrave set [Event]='ACD: ' + CallActionDesc, [EventData]=CallActionReasonDesc WHERE [Event] = 'ACD'

			UPDATE A
			  SET a.CallData='<p>SIN:' + B.Param1 + '</p>' + '<p>Caller Number: ' + B.Param2 + '</p>' +	'<p>Callback: ' + B.Param3 + '</p>' + '<p>Preferred Language: ' + B.Param4 + '</p>' +
				'<p>Customer Concern: ' + B.Param5 + '</p>' + '<p>Date Time: ' + B.Param6 + '</p>' + '<p>What Time: ' + B.Param7 + '</p>' +	'<p>Follow-up Count: ' + B.Param8 + '</p>' + 
				'<p>Skillset: ' + B.Param9 + '</p>' + '<p>EERT_Enabled: ' + B.Param10 + '</p>' +  '<p>EERT_MaxQueue: ' + B.Param11 + '</p>' + '<p>EERT_External: ' + B.Param12 + '</p>' +
				'<p>Trunkline: ' + B.Param13 + '</p>' +	'<p>RepeatCount: ' + B.Param14 + '</p>' + '<p>Data 16: ' + B.Param16 + '</p>' +	'<p>Segment: ' + B.Param17 + '</p>' +
				'<p>GCID: ' + B.Param18 + '</p>' + '<p>UMID: ' + B.Param19 + '</p>' + '<p>Data 20: ' + B.Param20 + '</p>'
			  FROM @tempCradleToGrave AS A INNER JOIN [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[MediaDataDetail] AS B ON B.SeqNum = A.SeqNum where a.EventID =3.1 and a.[Event]='ACD: Answered'

			UPDATE a set [Event]='Transfer to External', a.CallData = '<p>ANI: ' + b.ani + '</p><p>' + 'DNIS: ' + b.dnis + '</p>' from @tempCradleToGrave a
				inner join [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[IVRCallDetail] b on a.seqnum=b.seqnum	WHERE a.[Event] = 'M3' and a.Destination is null

			Declare @UPTABLE TABLE([UMID] NVARCHAR(50) NULL,[MinCallDate] datetime NULL,[MaxCallDate] datetime NULL,[Duration] Time(0) NULL);
			insert into @UPTABLE select distinct  UMID, MIN(CallDateTime) as MinCallDate, MAX(CallDateTime) as MaxCallDate,CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,max(CallDateTime)),'+08:00'))-
			CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,min(CallDateTime)),'+08:00')),108) as Duration from @tempCradleToGrave where umid in (
			select distinct umid from @tempCradleToGrave where Event in('ACD: Abandoned In Queue')) group by UMID
			 
			update a set a.EventData='Abandon Delay: ' + convert(varchar(30), b.duration) from @tempCradleToGrave a inner join @UPTABLE b on a.UMID=b.UMID  and a.Event in('ACD: Abandoned In Queue')
		 
			INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallTypeId, CallActionId, CallActionReasonId, CallDateTime,CallDate, CallTime, [Event], EventID, Agent, [Source], Destination, CallData, [EventData])
			select NULL as UMID, A.Seqnum, A.Service_Id, A.CallTypeId, A.CallActionId, A.CallActionReasonId, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.ConnClearDt),'+08:00')) as CallDateTime
			, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.ConnClearDt),'+08:00')),101) as CallDate
			, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.ConnClearDt),'+08:00')),108) as CallTime
			, 'Call Released: ' + D.CallActionReasonDesc as [Event], 5.1 as EventID, A.User_Id as Agent, NULL as [Source], NULL as Destination, 'ANI:' + A.ANI as CallData, 'Handling Time: ' + CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.ConnClearDt),'+08:00'))-
			CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.QueueEndDt),'+08:00')),108) as [EventData]
			FROM [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[ACDCallDetail] AS A INNER JOIN [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Service As B ON A.Service_Id = B.Service_Id
			INNER JOIN [VW12PCTIDB01\UIP_CONFIG].lookup.dbo.tlkpCallActionReason AS D ON A.CallActionReasonId = D.CallActionReasonId
			INNER JOIN [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Disposition
			As E ON A.AgentDispId = E.Disp_Id  WHERE CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime
		  
			UPDATE A
			SET A.CallActionId = B.CallActionId,A.CallActionReasonId = B.CallActionId,A.CallActionDesc = C.CallActionDesc, A.CallActionReasonDesc = D.CallActionReasonDesc
			FROM @tempCradleToGrave AS A INNER JOIN [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[ACDCallDetail] AS B ON B.SeqNum = A.Seqnum AND B.CallTypeId = A.CallTypeId 
			INNER JOIN [VW12PCTIDB01\UIP_CONFIG].[lookup].[dbo].[tlkpCallAction] AS C ON C.CallActionId = B.CallActionId
			INNER JOIN [VW12PCTIDB01\UIP_CONFIG].[lookup].[dbo].[tlkpCallActionReason] AS D ON D.CallActionReasonId = B.CallActionReasonId	where a.EventID =4.1

			INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallTypeId, CallDateTime, CallActionId, CallActionReasonId,CallDate, CallTime, [Event], EventID, Agent, [Source], Destination, CallData, [EventData])
			SELECT  NULL as UMID, A.Seqnum, A.Service_Id, A.CallTypeId, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.HoldStartDt),'+08:00')) as CallDateTime
			, NULL as CallActionID, NULL as CallActionReasonID, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.HoldStartDt),'+08:00')),101) as CallDate
			, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.HoldStartDt),'+08:00')),108) as CallTime
			, 'Call On Hold' as [Event], 4.1 as EventID, A.User_Id as Agent, NULL as [Source], NULL as Destination, NULL as CallData
			, 'Hold Time: ' + CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.HoldEndDt),'+08:00'))- CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.HoldStartDt),'+08:00')),108) as [EventData]
			FROM [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[OnCallActivityDetail]  AS A WHERE A.CallTypeId = 1 AND CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime

			INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallDateTime, CallDate, CallTime, [Event], EventID, Agent, [Source], Destination, CallData, [EventData])
			SELECT  NULL as UMID, A.Seqnum, A.Service_Id, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,ISNULL(A.ConnectDt,A.ConnClearDt)),'+08:00')) as CallDateTime
			, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,ISNULL(A.ConnectDt,A.ConnClearDt)),'+08:00')),101) as CallDate
			, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,ISNULL(A.ConnectDt,A.ConnClearDt)),'+08:00')),108) as Calltime
			, 'Consultation' as Event, 4.2 as EvetnID, A.User_Id as Agent, A.FirstPartyId as [Source], A.SecondPartyId as Destination, B.CallActionDesc as CallData
			, 'Consultation Time: ' + CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.ConnClearDt),'+08:00'))-
			CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,ISNULL(A.ConnectDt,A.ConnClearDt)),'+08:00')),108) as [EventData]
			FROM [REPDB].[dbo].[ConsultationCallDetail] as A LEFT JOIN [VW12PCTIDB01\UIP_CONFIG].[lookup].[dbo].[tlkpCallAction] AS B ON A.CallActionId = B.CallActionId 
			WHERE CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime

			update a set a.Source=b.UserFullName from @tempCradleToGrave a inner join [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Users b on a.Source=b.User_Id and a.EventID=4.2
			update a set a.Destination=b.UserFullName from @tempCradleToGrave a inner join [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Users b on a.Destination=b.User_Id and a.EventID=4.2

			INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallTypeId, CallDateTime, CallActionId, CallActionReasonId,CallDate, CallTime, [Event], EventID, Agent, [Source], Destination, CallData, [EventData])
			SELECT NULL as UMID,A.Seqnum, A.Service_Id, A.CallTypeId, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapCallStartDt),'+08:00')) as CallDateTime
			, NULL as CallActionID,NULL as CallActionReasonID, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapCallStartDt),'+08:00')),101) as CallDate
			, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapCallStartDt),'+08:00')),108) as CallTime, 'Manual Call During Wrap' as [Event]
			, 6.1 as EventID, A.User_Id as Agent, NULL as [Source], NULL as Destination, NULL as CallData, 'Handling Time: ' + CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapCallEndDt),'+08:00'))-
			CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapCallStartDt),'+08:00')),108) as [EventData]  FROM [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[OnCallActivityDetail]  AS A
			WHERE A.CallTypeId IN (9) AND CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime

			INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallTypeId, CallDateTime, CallActionId, CallActionReasonId,CallDate, CallTime, [Event], EventID, Agent, [Source], Destination, CallData, [EventData])
			SELECT NULL as UMID, A.Seqnum, A.Service_Id, A.CallTypeId, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapEndDt),'+08:00')) as CallDateTime, A.CallActionId, A.CallActionReasonId
			, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapEndDt),'+08:00')),101) as CallDate, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapEndDt),'+08:00')),108) as CallTime
			, 'Wrapup' , 7.1 as EventID, A.User_Id as Agent, NULL as [Source], NULL as Destination, 'ANI: ' + A.ANI as CallData, 'Wrapup Time: ' + CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapEndDt),'+08:00'))-
			CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.ConnClearDt),'+08:00')),108) + '' + '' + 'Agent Disp: ' + E.Disposition_desc
			FROM [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[ACDCallDetail] AS A INNER JOIN [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Service As B ON A.Service_Id = B.Service_Id
			INNER JOIN [VW12PCTIDB01\UIP_CONFIG].lookup.dbo.tlkpCallActionReason AS D ON A.CallActionReasonId = D.CallActionReasonId
			INNER JOIN [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Disposition As E ON A.AgentDispId = E.Disp_Id WHERE CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime

			update a set a.Agent=b.UserFullName from @tempCradleToGrave a inner join [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Users b on a.Agent=b.User_Id and a.Agent is not null  
			UPDATE A SET A.UMID = RIGHT(B.Param19,22) FROM @tempCradleToGrave AS A INNER JOIN [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[MediaDataDetail] AS B ON B.SeqNum = A.SeqNum WHERE A.UMID IS NULL
			END 

			 SELECT UMID, CallDateTime, Event, Agent as HandledBy, Source, Destination, CallData, EventData  into " . $tblname . " FROM @tempCradleToGrave WHERE NOT(UMID IS NULL) AND UMID <> '' 
			 AND CallDateTime between @FromDateTime and @ToDateTime ORDER BY UMID, EventID, SeqNum, CallDateTime, rn";
		$query = $this->db->query($cmd);
		return $tblname;
	}
	
	public function get_report_20($datefrom ,$dateto){
		$tblname = "vhis20_". date('mdYhis'). '_' . str_replace('-','',$this->session->userdata("Token")) ."";
		$cmd = "
	BEGIN 
	SET NOCOUNT ON;

DECLARE @tempCradleToGrave TABLE (
	[UMID] NVARCHAR(50) NULL,
	[SeqNum] NVARCHAR(20) NULL,
	[ServiceID] nvarchar(20) NULL,
	[CallTypeID] int,
	[CallDateTime] datetime NULL,
	[CallActionId] int NULL,
	[CallActionDesc] varchar(200) NULL,
	[CallActionReasonId] int NULL,
	[CallActionReasonDesc] varchar(200) NULL,
	[CallDate] nvarchar(20) NULL,
	[CallTime] nvarchar(20) NULL,
	[EventID] float,
	[Event] nvarchar(200) NULL,
	Agent nvarchar(200) NULL,
	[Source] nvarchar(200) NULL,
	Destination nvarchar(200) NULL,
	CallData nvarchar(MAX) NULL,
	[EventData] nvarchar(200) NULL,
	[rn] float
);


DECLARE @FromDateTime DATETIME;
DECLARE @ToDateTime DATETIME;
SET @FromDateTime = '".$datefrom."';
SET @ToDateTime = '".$dateto."';








INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallDateTime, CallDate, CallTime, [Event], EventID, Agent, [Source], Destination, CallData, [EventData])
SELECT RIGHT(DLG_UMID,22) AS UMID
	, NULL AS SeqNum
	, NULL AS ServiceID
	, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,UTC_TIME),'+08:00')) AS CallDateTime
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,UTC_TIME),'+08:00')),101) AS CallDate
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,UTC_TIME),'+08:00')),108) AS CallTime
	, 'CXP Inbound Call' AS [Event]
	, 0.1 as EventID
	, NULL AS Agent
	, DLG_DNIS as [Source]
	, NULL AS Destination
	, 'ANI: ' + DLG_ANI AS CallData
	, last_ds_name AS [EventData] 
	--'Script execution time: ' + CAST((SELFSERVICE_DUR_MS/1000) AS VARCHAR(10)) + ' second(s)' AS [EventData]
FROM 
[VW12PCTICXPDB01].[LOGDB].[voadmin].[VOLDDLGSTS] 
--[LOGDB].[dbo].[VOLDDLGSTS] 
WHERE NOT(DLG_UMID IS NULL)
AND CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,UTC_TIME),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime


--order by CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,UTC_TIME),'+08:00'))
;








INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallDateTime, CallDate, CallTime, [Event], EventID, Agent, [Source], Destination, CallData, [EventData])
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	, 'Routed to CXP IVR' as [Event]
	, 1.0 AS EventID
	, NULL as Agent
	, 'Cisco' as [Source]
	, 'DPA Agreement' as Destination
	, 'DNIS: ' + DNIS as CallData
	--,NULL as [EventData]
	, 'DPAAgreement:' + DPAAgreement  as [EventData]
FROM  
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE [Language] <> '' AND [Language] NOT IN ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	, 'Routed to CXP IVR' as [Event]
	, 1.1 AS EventID
	, NULL as Agent
	, 'DPA Agreement' as [Source]
	, 'Language' as Destination
	, 'DNIS: ' + DNIS as CallData
	--,NULL as [EventData]
	, 'Language: ' + [Language]  as [EventData]
FROM  
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE [Language] <> '' AND [Language] NOT IN ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	, 'IVR Script' as [Event]
	, 1.12 AS EventID
	, NULL as Agent
	, 'Language' as [Source]
	, 'Main Menu' as Destination
	, 'DNIS: ' + DNIS as CallData
	--,NULL as [EventData]
	--, 'Language: ' + [Language]  as [EventData]
	, 'Main Menu: ' + MainMenu  as [EventData]
FROM  
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE [Language] <> '' AND [Language] NOT IN ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

--UNION
--SELECT  RIGHT(UMID,22) as UMID
--	, NULL as SeqNum
--	, NULL as ServiceID
--	, CallDate as CallDateTime
--	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
--	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
--	, 'IVR Script' as [Event]
--	, 1.2 as EventID
--	, NULL as Agent
--	, 'Main Menu' as [Source]
--	, MainMenu as Destination
--	, 'DNIS: ' + DNIS as CallData
--	--,  NULL as [EventData]
--	, 'NewAppService: ' + NewApplicationService  as [EventData]
--FROM  
--[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
----[TransactionDB].[dbo].[IVRSMenu]
--WHERE MainMenu <> '' AND [MainMenu] NOT IN ('Abandoned', 'Terminated', 'Transfer')
--AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	, 'IVR Script' as [Event]
	, 1.3 AS EventID
	, NULL as Agent
	, 'OutagesAndIncidents' as [Source]
	, OutagesAndIncidents as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData]
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE [OutagesAndIncidents] <> '' AND [OutagesAndIncidents] NOT IN ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	, 'IVR Script' as [Event]
	, 1.4 AS EventID
	, NULL as Agent
	, 'BillingAndPayments' as [Source]
	, BillingAndPayments as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData] 
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu] 
WHERE [BillingAndPayments] <> '' AND [BillingAndPayments] NOT IN ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	, 'IVR Script' as [Event]
	, 1.5 AS EventID
	, NULL as Agent
	, 'ProductsServicesAndPrograms' as [Source]
	, ProductsServicesAndPrograms as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData] 
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE [ProductsServicesAndPrograms] <> ''  AND [ProductsServicesAndPrograms] NOT IN ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	, 'IVR Script' As [Event]
	, 1.6 AS EventID
	, NULL as Agent
	, 'Others' as [Source]
	, Others as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData] 
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE [Others] <> '' AND [Others] NOT IN ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	,'IVR Script' as [Event]
	, 1.7 AS EventID
	, NULL as Agent
	, 'Other Menu' as [Source]
	, OtherMenus as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData]
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE [OtherMenus] <> '' AND [OtherMenus] NOT IN ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	--, [Language] as [Event]
	, 'IVR Script' As [Event]
	, 2.1 AS EventID
	, NULL as Agent
	--, NULL as [Source]
	, 'Language' as [Source]
	--, NULL as Destination
	,[Language] as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData]
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE [Language] IN  ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as Agent
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	--, [MainMenu] as [Event]
	, 'IVR Script' As [Event]
	, 2.2 AS EventID
	, NULL as Agent
	--, NULL as [Source]
	, 'Main Menu' as [Source]
	--, NULL as Destination
	, [MainMenu] as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData]
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE [MainMenu] IN  ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	--, OutagesAndIncidents as [Event]
	, 'IVR Script' As [Event]
	, 2.3 AS EventID
	, NULL as Agent
	--, NULL as [Source]
	, 'OutagesAndIncidents' as [Source]
	--, NULL as Destination
	,[OutagesAndIncidents] as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData]
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE [OutagesAndIncidents] IN  ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	--, BillingAndPayments as [Event]
	, 'IVR Script' As [Event]
	, 2.4 AS EventID
	, NULL as Agent
	--, NULL as [Source]
	, 'BillingAndPayments' as [Source]
	--, NULL as Destination
	, BillingAndPayments as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData]
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE BillingAndPayments IN  ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	--, ProductsServicesAndPrograms as [Event]
	, 'IVR Script' As [Event]
	, 2.5 AS EventID
	, NULL as Agent
	--, NULL as [Source]
	, 'ProductsServicesAndPrograms' as [Source]
	--, NULL as Destination
	, ProductsServicesAndPrograms as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData]
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE ProductsServicesAndPrograms IN  ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	--, Others as [Event]
	, 'IVR Script' As [Event]
	, 2.6 AS EventID
	, NULL as Agent
	--, NULL as [Source]
	, 'Others' as [Source]
	--, NULL as Destination
	, Others as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData]
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE Others IN  ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	--, OtherMenus as [Event]
	, 'IVR Script' As [Event]
	, 2.7 AS EventID
	, NULL as Agent
	--, NULL as [Source]
	, 'OtherMenus' as [Source]
	--, NULL as Destination
	, OtherMenus as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData]
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE OtherMenus IN  ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime;


--IVRNODEACTIVITYDETAIL

;with CTE as (
SELECT  
	--RIGHT(MDET.Param19,22) as UMID
	 
	NULL as UMID
	, IVRN.SeqNum as SeqNum
	, IVRN.Service_Id as ServiceID
	, IVRN.NodeTypeId as CallTypeId
	, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,IVRN.CallStartDt),'+08:00')) as CallDateTime
	, IVRN.NodeActionID as CallActionID 
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,IVRN.CallStartDt),'+08:00')),101) as CallDate
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,IVRN.CallStartDt),'+08:00')),108) as CallTime
	, NA.NodeActionDesc as [Event]
	--, NA.NodeTypeDesc as [Event]
	, 2.8 as EventID
	, NULL as Agent
	, NULL as [Source]
	, NULL as Destination
	, NULL as CallData
	, IVRO.Caption as EventData
	,ROW_NUMBER()OVER(Order by ivrn.seqnum) as rn
	--,ROW_NUMBER() OVER(Order by ivro.caption) as rn
	, ivro.caption as caption
	--, NA.NodeActionDesc as EventData
 FROM [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[IVRNodeActivityDetail] IVRN
 left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[IVRScriptObjects] IVRO  on IVRN.ScriptID=IVRO.ScriptId and IVRN.ObjectId=IVRO.ObjectId 
 --left join [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[MediaDataDetail] MDET on IVRN.SeqNum=MDET.Seqnum and IVRN.CallId=MDET.CallId
 left join [VW12PCTIDB01\UIP_CONFIG].[lookup].[dbo].[tlkpNodeActions] as NA on IVRN.NodeActionID=NA.NodeActionId
WHERE
CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,IVRN.CallStartDt),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime

)


INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallTypeId, CallDateTime, CallActionId, CallDate, CallTime, [Event], 
	EventID, Agent, [Source], Destination, CallData, [EventData], rn)
select distinct base.umid, base.SeqNum, base.serviceID, base.CallTypeId, base.CallDateTime, base.CallActionID,
 base.CallDate, base.CallTime, base.Event, base.EventID, base.Agent, base.caption as [Source], nxt.caption as Destination, NULL as CallData, Null as [EventData], base.rn 
from cte base
left join cte nxt on nxt.rn=base.rn+1 and nxt.SeqNum=base.SeqNum 
--order by base.SeqNum, base.rn





--Start Insert UIP Data from Call Detail

;with CTE1 as (
SELECT RIGHT(A.UMID,22) as UMID
	, A.SeqNum
	, A.Service_Id
	, A.CallTypeId
	, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')) as CallDateTime
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')),101) as CallDate
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')),108) as CallTime
	, B.CallTypeDesc as [Event]
	, 3.1 as EventID
	, A.User_id as Agent
	, C.Service_c as [Source]
	, NULL as Destination
	, 'ANI:' + A.ANI as CallData
	, NULL as [EventData]
	, ROW_NUMBER()OVER(Order by A.seqnum, a.calltypeid desc) as rn
	, C.Service_c as caption
FROM 
[VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[CallDetail] as A
--[detail_epro].[dbo].[CallDetail] AS A
INNER JOIN [VW12PCTIDB01\UIP_CONFIG].lookup.dbo.tlkpCallType As B ON A.CallTypeId = B.CallTypeId
INNER JOIN 
[VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Service
--config_epro.dbo.Service 
	As C ON A.Service_Id = C.Service_Id
WHERE A.CallCategoryId = 1 and A.Service_id <> 4000085
and CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime
--order by a.CallStartDt
)

INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallTypeId, CallDateTime, CallDate, CallTime, [Event], EventID
	, Agent, [Source], Destination, CallData, [EventData], rn)

select distinct base1.UMID, base1.SeqNum, base1.Service_Id, base1.CallTypeId, base1.CallDateTime,
 base1.CallDate, base1.CallTime, base1.Event, base1.EventID, base1.Agent, base1.Source as [Source], nxt1.caption as Destination, NULL as CallData, Null as [EventData], base1.rn 
from cte1 base1
left join cte1 nxt1 on nxt1.rn=base1.rn+1 and nxt1.SeqNum=base1.SeqNum 
order by base1.SeqNum, base1.CallDateTime, base1.rn



UPDATE A
SET A.CallActionId = B.CallActionId, 
A.CallActionReasonId = B.CallActionId, 
A.CallActionDesc = C.CallActionDesc, 
--A.CallActionReasonDesc = D.CallActionReasonDesc +  CHAR(13) + CHAR(10) + 'Queue Time: ' + 
A.CallActionReasonDesc = case B.CallActionId
							when 5 then 'Abandon Delay: ' 
							when 6 then 'Abandon Delay: ' 
							when 23 then 'Abandon Delay: ' 
							when 24 then 'Abandon Delay: ' 
							else 'Queue Time: '
						 end + 
	CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,B.QueueEndDt),'+08:00'))-
	CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,B.QueueStartDt),'+08:00')),108) 
FROM @tempCradleToGrave AS A
 	INNER JOIN 
	[VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[ACDCallDetail] 
	--[detail_epro].[dbo].[ACDCallDetail] 
	AS B ON B.SeqNum = A.Seqnum AND B.CallTypeId = A.CallTypeId 
	INNER JOIN [VW12PCTIDB01\UIP_CONFIG].[lookup].[dbo].[tlkpCallAction] AS C ON C.CallActionId = B.CallActionId
	INNER JOIN [VW12PCTIDB01\UIP_CONFIG].[lookup].[dbo].[tlkpCallActionReason] AS D ON D.CallActionReasonId = B.CallActionReasonId
	where a.EventID =3.1 --and a.Event='ACD: Answered'




UPDATE @tempCradleToGrave
set [Event]='ACD: ' + CallActionDesc, [EventData]=CallActionReasonDesc
--SET [EventData] = [Event] + ': ' + CallActionDesc + '; ' + CallActionReasonDesc 
--SET [EventData] = CallActionDesc + '; ' + CallActionReasonDesc 
WHERE [Event] = 'ACD'

UPDATE A
  SET a.CallData=
	'<p>SIN:' + B.Param1 + '</p>' + 
	'<p>Caller Number: ' + B.Param2 + '</p>' +
	'<p>Callback: ' + B.Param3 + '</p>' +
	'<p>Preferred Language: ' + B.Param4 + '</p>' +
	'<p>Customer Concern: ' + B.Param5 + '</p>' +
	'<p>Date Time: ' + B.Param6 + '</p>' +
	'<p>What Time: ' + B.Param7 + '</p>' +
	'<p>Follow-up Count: ' + B.Param8 + '</p>' + 
	'<p>Skillset: ' + B.Param9 + '</p>' +
	'<p>EERT_Enabled: ' + B.Param10 + '</p>' +  
	'<p>EERT_MaxQueue: ' + B.Param11 + '</p>' +
	'<p>EERT_External: ' + B.Param12 + '</p>' +
	'<p>Trunkline: ' + B.Param13 + '</p>' +
	'<p>RepeatCount: ' + B.Param14 + '</p>' +
	'<p>Data 16: ' + B.Param16 + '</p>' +
	'<p>Segment: ' + B.Param17 + '</p>' +
	'<p>GCID: ' + B.Param18 + '</p>' +
	'<p>UMID: ' + B.Param19 + '</p>' +
	'<p>Data 20: ' + B.Param20 + '</p>'
  --A.UMID = RIGHT(B.Param19,22)
  --,	A.Destination = B.Param5 + ' - ' + B.Param9
  --,	A.CallData = CASE B.Param1
		--			WHEN NULL THEN 'ANI: ' + A.CallData 
		--			WHEN '' THEN 'ANI: ' + A.CallData 
		--			ELSE 'ANI: ' + A.CallData + CHAR(13) + CHAR(10) + 'SIN: ' + B.Param1
		--		END
  FROM @tempCradleToGrave AS A
		INNER JOIN [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[MediaDataDetail] AS B ON B.SeqNum = A.SeqNum
where a.EventID =3.1 and a.[Event]='ACD: Answered'

UPDATE a
	set [Event]='Transfer to External' 
	, a.CallData = '<p>ANI: ' + b.ani + '</p><p>' + 'DNIS: ' + b.dnis + '</p>'
	from @tempCradleToGrave a
	inner join [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[IVRCallDetail] b on a.seqnum=b.seqnum
	WHERE a.[Event] = 'M3' and a.Destination is null


Declare @UPTABLE TABLE(
	[UMID] NVARCHAR(50) NULL,
	--[SeqNum] NVARCHAR(20) NULL INDEX IXS NONCLUSTERED,
	[MinCallDate] datetime NULL,
	[MaxCallDate] datetime NULL,
	[Duration] Time(0) NULL
	);


insert into @UPTABLE 
 select distinct  UMID, MIN(CallDateTime) as MinCallDate, MAX(CallDateTime) as MaxCallDate
 ,CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,max(CallDateTime)),'+08:00'))-
CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,min(CallDateTime)),'+08:00')),108) as Duration
 from @tempCradleToGrave where umid in (
 select distinct umid from @tempCradleToGrave where Event in('ACD: Abandoned In Queue'))
group by UMID

update a set a.EventData='Abandon Delay: ' + convert(varchar(30), b.duration)
 from @tempCradleToGrave a inner join @UPTABLE b on a.UMID=b.UMID  and a.Event in('ACD: Abandoned In Queue')

 --End Insert UIP Data from Call Detail

 
 --Start Insert UIP Data from AgentDispoDetail
 INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallTypeId, CallActionId, CallActionReasonId, CallDateTime,
	CallDate, CallTime, [Event], EventID, Agent, [Source], Destination, CallData, [EventData])
 select NULL as UMID
	, A.Seqnum
	, A.Service_Id
	, A.CallTypeId
	, A.CallActionId
	, A.CallActionReasonId	
	, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.ConnClearDt),'+08:00')) as CallDateTime
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.ConnClearDt),'+08:00')),101) as CallDate
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.ConnClearDt),'+08:00')),108) as CallTime
	, 'Call Released: ' + D.CallActionReasonDesc as [Event]
	, 5.1 as EventID
	, A.User_Id as Agent
	, NULL as [Source]
	, NULL as Destination
	, 'ANI:' + A.ANI as CallData
	, 'Handling Time: ' + CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.ConnClearDt),'+08:00'))-
	CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.QueueEndDt),'+08:00')),108) as [EventData]
	--+ CHAR(13) + CHAR(10) + 'Agent Disp: ' + E.Disposition_desc
   FROM 
    [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[ACDCallDetail] AS A
   --[detail_epro].[dbo].[ACDCallDetail] AS A
  INNER JOIN 
  [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Service 
  --config_epro.dbo.Service 
  As B ON A.Service_Id = B.Service_Id
  INNER JOIN [VW12PCTIDB01\UIP_CONFIG].lookup.dbo.tlkpCallActionReason AS D ON A.CallActionReasonId = D.CallActionReasonId
  INNER JOIN 
   [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Disposition
  --config_epro.dbo.Disposition 
  As E ON A.AgentDispId = E.Disp_Id
  WHERE CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime
  --End Insert UIP Data from AgentDispoDetail
  
  
UPDATE A
	SET A.CallActionId = B.CallActionId, 
	A.CallActionReasonId = B.CallActionId, 
	A.CallActionDesc = C.CallActionDesc, A.CallActionReasonDesc = D.CallActionReasonDesc
	FROM @tempCradleToGrave AS A
 		INNER JOIN 
		[VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[ACDCallDetail]
		--[detail_epro].[dbo].[ACDCallDetail] 
		AS B ON B.SeqNum = A.Seqnum AND B.CallTypeId = A.CallTypeId 
		INNER JOIN [VW12PCTIDB01\UIP_CONFIG].[lookup].[dbo].[tlkpCallAction] AS C ON C.CallActionId = B.CallActionId
		INNER JOIN [VW12PCTIDB01\UIP_CONFIG].[lookup].[dbo].[tlkpCallActionReason] AS D ON D.CallActionReasonId = B.CallActionReasonId
		where a.EventID =4.1


--Start Hold Calls
INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallTypeId, CallDateTime, CallActionId, CallActionReasonId, 
CallDate, CallTime, [Event], EventID, Agent, [Source], Destination, CallData, [EventData])
SELECT  NULL as UMID
	, A.Seqnum
	, A.Service_Id
	, A.CallTypeId
	, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.HoldStartDt),'+08:00')) as CallDateTime
	, NULL as CallActionID
	, NULL as CallActionReasonID
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.HoldStartDt),'+08:00')),101) as CallDate
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.HoldStartDt),'+08:00')),108) as CallTime
	, 'Call On Hold' as [Event]
	, 4.1 as EventID
	, A.User_Id as Agent
	, NULL as [Source]
	, NULL as Destination
	, NULL as CallData
	
	, 'Hold Time: ' + CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.HoldEndDt),'+08:00'))-
		CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.HoldStartDt),'+08:00')),108) as [EventData]
   FROM 
    [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[OnCallActivityDetail]  AS A
   --[detail_epro].[dbo].[OnCallActivityDetail] AS A
  WHERE A.CallTypeId = 1
  AND CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime


--End Hold Calls




--End Hold Calls
--Start Consultaion
INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallDateTime, CallDate, CallTime, [Event], EventID, Agent, [Source], Destination, CallData, [EventData])
SELECT  NULL as UMID
	, A.Seqnum
	, A.Service_Id
	, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,ISNULL(A.ConnectDt,A.ConnClearDt)),'+08:00')) as CallDateTime
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,ISNULL(A.ConnectDt,A.ConnClearDt)),'+08:00')),101) as CallDate
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,ISNULL(A.ConnectDt,A.ConnClearDt)),'+08:00')),108) as Calltime
	, 'Consultation' as Event
	, 4.2 as EvetnID
	, A.User_Id as Agent
	, A.FirstPartyId as [Source]
	, A.SecondPartyId as Destination
	, B.CallActionDesc as CallData
	
	, 'Consultation Time: ' + CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.ConnClearDt),'+08:00'))-
		CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,ISNULL(A.ConnectDt,A.ConnClearDt)),'+08:00')),108) as [EventData]
	--,'Consultation Duration: ' + CAST(DATEDIFF(second,ISNULL(A.ConnectDt,A.ConnClearDt),A.ConnClearDt) AS nvarchar) as [EventData]
	FROM [REPDB].[dbo].[ConsultationCallDetail] as A
	LEFT JOIN [VW12PCTIDB01\UIP_CONFIG].[lookup].[dbo].[tlkpCallAction] AS B ON A.CallActionId = B.CallActionId 
  WHERE CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime

  
update a set a.Source=b.UserFullName
 from @tempCradleToGrave a inner join 
 [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Users
 --[config_epro].[dbo].[Users] 
 b on a.Source=b.User_Id and a.EventID=4.2

 update a set a.Destination=b.UserFullName
 from @tempCradleToGrave a inner join 
 [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Users
 --[config_epro].[dbo].[Users] 
 b on a.Destination=b.User_Id and a.EventID=4.2

--end consultaion
--Manual Call on Wrap State
 INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallTypeId, CallDateTime, CallActionId, CallActionReasonId, 
	CallDate, CallTime, [Event], EventID, Agent, [Source], Destination, CallData, [EventData])
SELECT NULL as UMID
	,A.Seqnum
	, A.Service_Id
	, A.CallTypeId
	, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapCallStartDt),'+08:00')) as CallDateTime
	, NULL as CallActionID
	, NULL as CallActionReasonID
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapCallStartDt),'+08:00')),101) as CallDate
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapCallStartDt),'+08:00')),108) as CallTime
	, 'Manual Call During Wrap' as [Event]
	, 6.1 as EventID
	, A.User_Id as Agent
	, NULL as [Source]
	, NULL as Destination
	, NULL as CallData
	, 'Handling Time: ' + CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapCallEndDt),'+08:00'))-
	CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapCallStartDt),'+08:00')),108) as [EventData]
   FROM 
   [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[OnCallActivityDetail]  AS A
   --[detail_epro].[dbo].[OnCallActivityDetail] AS A
  WHERE A.CallTypeId IN (9) AND CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime


--start insert wrapup time
  INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallTypeId, CallDateTime, CallActionId, CallActionReasonId, 
	CallDate, CallTime, [Event], EventID, Agent, [Source], Destination, CallData, [EventData])
  SELECT NULL as UMID
	, A.Seqnum
	, A.Service_Id
	, A.CallTypeId
	, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapEndDt),'+08:00')) as CallDateTime
	, A.CallActionId
	, A.CallActionReasonId
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapEndDt),'+08:00')),101) as CallDate
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapEndDt),'+08:00')),108) as CallTime
	, 'Wrapup' --'Agent Disp: ' + E.Disposition_desc, --+ D.CallActionReasonDesc,  
	, 7.1 as EventID
	, A.User_Id as Agent
	, NULL as [Source]
	, NULL as Destination
	, 'ANI: ' + A.ANI as CallData
	, 'Wrapup Time: ' + CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapEndDt),'+08:00'))-
	CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.ConnClearDt),'+08:00')),108) + '
' + '
' + 'Agent Disp: ' + E.Disposition_desc
   FROM 
    [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[ACDCallDetail] AS A
   --[detail_epro].[dbo].[ACDCallDetail] AS A
  INNER JOIN 
  [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Service
  --config_epro.dbo.Service 
  As B ON A.Service_Id = B.Service_Id
  INNER JOIN [VW12PCTIDB01\UIP_CONFIG].lookup.dbo.tlkpCallActionReason AS D ON A.CallActionReasonId = D.CallActionReasonId
  INNER JOIN 
  [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Disposition
  --config_epro.dbo.Disposition 
  As E ON A.AgentDispId = E.Disp_Id
  WHERE CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime

  --end wrapup time

  


update a set a.Agent=b.UserFullName
 from @tempCradleToGrave a inner join 
 [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Users
 --[config_epro].[dbo].[Users] 
 b on a.Agent=b.User_Id and a.Agent is not null  


UPDATE A
  SET A.UMID = RIGHT(B.Param19,22)
  --,	A.Destination = B.Param5 + ' - ' + B.Param9
  --,	A.CallData = CASE B.Param1
		--			WHEN NULL THEN 'ANI: ' + A.CallData 
		--			WHEN '' THEN 'ANI: ' + A.CallData 
		--			ELSE 'ANI: ' + A.CallData + CHAR(13) + CHAR(10) + 'SIN: ' + B.Param1
		--		END
  FROM @tempCradleToGrave AS A
		INNER JOIN [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[MediaDataDetail] AS B ON B.SeqNum = A.SeqNum
WHERE A.UMID IS NULL


END 

 --SELECT UMID, CallDateTime, Event, Agent as HandledBy, Source, Destination, CallData, EventData  
 --FROM @tempCradleToGrave WHERE NOT(UMID IS NULL) AND UMID <> '' 


 SELECT * into " . $tblname . " FROM @tempCradleToGrave where CallDateTime between @FromDateTime and @ToDateTime
  AND NOT(UMID IS NULL) AND UMID <> '' 
  --and Event <> 'M3'
 ORDER BY UMID, EventID, SeqNum, CallDateTime, rn

 
 
";
					
	$query = $this->db->query($cmd);
		return $tblname;
	}
	
	

	public function get_report_13($datefrom,$dateto,$skill,$service,$siteid){
					
		$siteid = rtrim($siteid,",");
		$temp = explode(",",$siteid);
		$sid = "'" . implode ( "', '", $temp ) . "'";		
		$skillid = rtrim($skill,",");		
		$serviceid = rtrim($service,",");		
		
		$cmd = "SELECT	DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail)) / 15 * 15, 0)) as '15MinsInterval',
			DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail)) / 30 * 30, 0)) as '30MinsInterval',
			DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail)) / 60 * 60, 0)) as '60MinsInterval',
			CONVERT(VARCHAR(20), (dateadd(dd, 0 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail))) %7 , dateadd(hour, 0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail))), 101) as Sunday,
			CONVERT(VARCHAR(20), (dateadd(dd, 6 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail))) %7 , dateadd(hour, 0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail))), 101) as Saturday,
			convert(varchar(10), dateadd(hour, 0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail), 101) + ' ' +
			format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail)) / 15 * 15, 0)) as Datetime), 'hh:mm tt') as Minus15Mins,
			convert(varchar(10), dateadd(hour, 0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail), 101) + ' ' +
			format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail)) / 30 * 30, 0)) as Datetime), 'hh:mm tt') as Minus30Mins,
			convert(varchar(10), dateadd(hour, 0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail), 101) + ' ' +
			format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail)) / 60 * 60, 0)) as Datetime), 'hh:mm tt') as Minus60Mins,
			datepart(wk, dateadd(hour, 0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail)) as Week_count,datepart(w, dateadd(hour, 0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail)) as Day_Count, 
			datename(dw, dateadd(hour, 0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail)) as Day_Name,convert(varchar(10), dateadd(hour, 0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail), 101) as perDate,
			DATEPART(M, DATEADD(HOUR, 0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail)) AS Month_Count,
			cast(datename(m, dateadd(hour,0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail)) as varchar(10)) + ', ' + cast(year(dateadd(hour,0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail)) as varchar(10)) AS MONTH_NAME,
			cast(year(dateadd(hour,0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail)) as varchar(10)) + ', ' + cast(datename(m, dateadd(hour,0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail)) as varchar(10)) as Year_Month,
			YEAR(DATEADD(HOUR, 0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail)) AS YEAR,DATEADD(HOUR, 0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail) AS DATE_TIME,
			case when datepart(hour, dateadd(hour, 0, _tblCallbackDataDetails.DateTimeofCall_acdCallDetail)) <= 5 then convert(varchar(10), dateadd(DAY, -1,dateadd(hour, 0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail)), 101) 
			else convert(varchar(10), dateadd(hour, 0,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail), 101) end + ' ' +
			case when datepart(hour, dateadd(hour, 0, _tblCallbackDataDetails.DateTimeofCall_acdCallDetail)) <= 5 or datepart(hour, dateadd(hour, 0, _tblCallbackDataDetails.DateTimeofCall_acdCallDetail)) >= 22 then '10PM - 6AM'
			when datepart(hour, dateadd(hour, 0, _tblCallbackDataDetails.DateTimeofCall_acdCallDetail)) > = 14 and datepart(hour, dateadd(hour, 0, _tblCallbackDataDetails.DateTimeofCall_acdCallDetail)) <= 21 then '2PM - 10PM'
			when datepart(hour, dateadd(hour, 0, _tblCallbackDataDetails.DateTimeofCall_acdCallDetail)) >= 6 and datepart(hour, dateadd(hour, 0, _tblCallbackDataDetails.DateTimeofCall_acdCallDetail)) <= 13 then '6AM - 2PM' end
			as Service_Shift,_tblCallbackDataDetails.DateTimeofCall_acdCallDetail,_tblCallbackDataDetails.SeqNum_acdCallDetail,_tblCallbackDataDetails.ServiceId_acdCallDetail,_tblCallbackDataDetails.ContactNumber_acdCallDetail
		,_tblCallbackDataDetails.ServiceApplication	,_tblCallbackDataDetails.TotalAbandonedCalls,_tblCallbackDataDetails.TotalOfferedCalls,_tblCallbackDataDetails.callstartDate DateTimeofCallback_callBack
		,_tblCallbackDataDetails.ContactNumber_callBack	,_tblCallbackDataDetails.SeqNum_callBack,_tblCallbackDataDetails.CallActionId,_tempTableLastIVRSkillset.Skill_Id,_tempTableLastIVRSkillset.Skillset
		,_tblCallbackDataDetails.Disposition,_tblCallbackDataDetails.TotalCallback,_tempTableLastIVRSkillset.LastIVRInteraction	,case when _tempTableLastIVRSkillset.Skillset = 'Res_StreetLigth_sk' then 'Res_StreetLight_sk' else _tempTableLastIVRSkillset.Skillset end as Skillset
		,_tempTableLastIVRSkillset.SIN_Number,isnull(_tempTableLastIVRSkillset.Skill_Id, (select top 1 skill_id from [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Skills where skill_desc LIKE _tempTableLastIVRSkillset.Skillset)) as Skill_Id
		,_tblCallbackDataDetails.SiteName
		FROM (	SELECT _tblAbandonedCallsWithCallbackList.DateTimeofCall_acdCallDetail,_tblAbandonedCallsWithCallbackList.SeqNum_acdCallDetail,_tblAbandonedCallsWithCallbackList.ServiceId_acdCallDetail
			,_tblAbandonedCallsWithCallbackList.ContactNumber_acdCallDetail,_tblAbandonedCallsWithCallbackList.ServiceApplication,_tblAbandonedCallsWithCallbackList.TotalAbandonedCalls
			,_tblAbandonedCallsWithCallbackList.TotalOfferedCalls,_tblAbandonedCallsWithCallbackList.DateTimeofCallback_callBack,_tblAbandonedCallsWithCallbackList.ContactNumber_callBack	
			,_tblAbandonedCallsWithCallbackList.SeqNum_callBack,_tblAbandonedCallsWithCallbackList.CallActionId,_tblMediaDataDetail.ServiceId_SIN,_tblMediaDataDetail.Disposition
			,_tblMediaDataDetail.TotalCallback,_tblAbandonedCallsWithCallbackList.SiteName,_tblAbandonedCallsWithCallbackList.callstartDate
		FROM (SELECT _tblAbandonedCalls.DateTimeofCall_acdCallDetail,_tblAbandonedCalls.SeqNum_acdCallDetail,_tblAbandonedCalls.ServiceId_acdCallDetail,_tblAbandonedCalls.ContactNumber_acdCallDetail
		,_tblAbandonedCalls.ServiceApplication,_tblAbandonedCalls.TotalAbandonedCalls,_tblAbandonedCalls.TotalOfferedCalls,_tblAbandonedCalls.CallActionId,_tblCallbackList.DateTimeofCallback_callBack
		,_tblCallbackList.ContactNumber_callBack,_tblCallbackList.SeqNum_callBack,_tblCallbackList.callstartDate,_tblAbandonedCalls.SiteName
		FROM (SELECT DATEADD(HOUR, 8, _acdCallDetail.CallStartDt) AS DateTimeofCall_acdCallDetail,_acdCallDetail.SeqNum AS SeqNum_acdCallDetail,_acdCallDetail.Service_Id AS ServiceId_acdCallDetail
			,_acdCallDetail.CallActionId,_acdCallDetail.ANI AS ContactNumber_acdCallDetail
			,CASE WHEN _acdCallDetail.CallActionId IN (5, 6) THEN COUNT(DISTINCT _acdCallDetail.SeqNum) END AS TotalAbandonedCalls
			,CASE WHEN _acdCallDetail.CallActionId IS NOT NULL THEN COUNT(DISTINCT _acdCallDetail.SeqNum) END AS TotalOfferedCalls
			,_service.Service_c AS ServiceApplication,isnull(sites.SiteName,'MOC') as SiteName
		FROM [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[ACDCallDetail] _acdCallDetail INNER JOIN [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Service] _service
			ON _service.Service_Id = _acdCallDetail.Service_Id	left join RepUIDB..Stations stations on _acdCallDetail.Station = stations.Station left join RepUIDB..Sites sites on stations.SiteGuid = sites.SiteGuid
		WHERE (DATEADD(HOUR, 8, _acdCallDetail.CallStartDt) >= '".$datefrom."' AND DATEADD(HOUR, 8, _acdCallDetail.CallStartDt)  <= '".$dateto."') AND _acdCallDetail.CallActionId IN (5, 6, 8)
		GROUP BY _acdCallDetail.CallStartDt,_acdCallDetail.SeqNum,_acdCallDetail.Service_Id,_acdCallDetail.CallActionId,_acdCallDetail.ANI,_service.Service_c,sites.SiteName) _tblAbandonedCalls
		LEFT JOIN (	SELECT	_callBack.Service_Id,DATEADD(HOUR, 8,_callBack.CallBack_dt) AS DateTimeofCallback_callBack,_callBack.Phone_Num AS ContactNumber_callBack,_callBack.Memo
			,(DATEADD(HOUR, 8, aod.CallStartDt) ) callstartDate,LTRIM(RTRIM((SELECT SUBSTRING(SUBSTRING(_callBack.Memo,CHARINDEX('< ',_callBack.Memo)+1,LEN(_callBack.Memo)),0,CHARINDEX(' >',SUBSTRING(_callBack.Memo,CHARINDEX('< ',_callBack.Memo)+1,LEN(_callBack.Memo))))))) AS SeqNum_callBack
		FROM [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[CallBack] _callBack, Repdb.dbo.AODCallDetail  aod
		WHERE _callBack.Memo like 'Aba%' and LTRIM(RTRIM((SELECT SUBSTRING(SUBSTRING(_callBack.Memo,CHARINDEX('< ',_callBack.Memo)+1,LEN(_callBack.Memo)),0,CHARINDEX(' >',SUBSTRING(_callBack.Memo,CHARINDEX('< ',_callBack.Memo)+1,LEN(_callBack.Memo))))))) =  aod.seqnum
		GROUP BY _callBack.Service_Id,_callBack.CallBack_dt,_callBack.Phone_Num,_callBack.Memo,(DATEADD(HOUR, 8, aod.CallStartDt) )	) _tblCallbackList
		ON _tblCallbackList.SeqNum_callBack = _tblAbandonedCalls.SeqNum_acdCallDetail) _tblAbandonedCallsWithCallbackList
		LEFT JOIN (	SELECT	DISTINCT _mediaDataDetail.SeqNum AS SeqNum_mediaDataDetail,_mediaDataDetail.Service_Id AS ServiceId_mediaDataDetail,_mediaDataDetail.CallTypeId,_mediaDataDetail.Param1 AS ServiceId_SIN
			,_mediaDataDetail.AgentDispId,_mediaDataDetail.Param2 AS ContactNumber_mediaDataDetail,COUNT (DISTINCT _mediaDataDetail.SeqNum) AS TotalCallback,_disposition.Disposition_Desc AS Disposition
		FROM [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[MediaDataDetail] _mediaDataDetail INNER JOIN [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].Disposition _disposition
			ON _disposition.Disp_Id = _mediaDataDetail.AgentDispId
		WHERE _mediaDataDetail.CallTypeId IN ('2') AND _mediaDataDetail.AgentDispId IS NOT NULL AND _mediaDataDetail.Service_Id = '4000003' AND _mediaDataDetail.Param2 <> ''
		GROUP BY _mediaDataDetail.SeqNum,_mediaDataDetail.Service_Id,_mediaDataDetail.CallTypeId,_mediaDataDetail.Param1,_mediaDataDetail.AgentDispId,_mediaDataDetail.Param2,_mediaDataDetail.Param5
			,_mediaDataDetail.Param9,_disposition.Disposition_Desc) _tblMediaDataDetail	ON _tblMediaDataDetail.SeqNum_mediaDataDetail = _tblAbandonedCallsWithCallbackList.SeqNum_acdCallDetail) _tblCallbackDataDetails
		left JOIN (	SELECT	DISTINCT _tempTableSkillset.SeqNum_skills,_tempTableSkillset.SIN_Number,_tempTableSkillset.LastIVRInteraction,_tempTableSkillset.Skillset,_tempMasterListSkills.Skill_Id
		FROM (SELECT DISTINCT _tempTableSkills.SeqNum AS SeqNum_skills,_tempTableSkills.Param1 AS SIN_Number,_tempTableSkills.Param5 As LastIVRInteraction,CASE WHEN SUBSTRING(_tempTableSkills.Param9,CHARINDEX('|',_tempTableSkills.Param9)+1,LEN(_tempTableSkills.Param9)) = 'Res_Services_App_sk '
			then 'Res_Services_App_sk' WHEN SUBSTRING(_tempTableSkills.Param9,CHARINDEX('|',_tempTableSkills.Param9)+1,LEN(_tempTableSkills.Param9)) = 'Res_StreetLight_sk'
			then 'Res_StreetLight_sk' else SUBSTRING(_tempTableSkills.Param9,CHARINDEX('|',_tempTableSkills.Param9)+1,LEN(_tempTableSkills.Param9)) end AS Skillset
		FROM (SELECT DISTINCT _tempSkills.SeqNum,_tempSkills.Param1,_tempSkills.Param5,_tempSkills.Param9 FROM repdb.dbo.[MediaDataDetail] _tempSkills
		WHERE _tempSkills.Param9 like '%sk%' AND _tempSkills.Param5 IS NOT NULL	) _tempTableSkills	) _tempTableSkillset
		LEFT JOIN [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Skills] _tempMasterListSkills ON _tempMasterListSkills.Skill_Desc = _tempTableSkillset.Skillset) _tempTableLastIVRSkillset
		ON _tempTableLastIVRSkillset.SeqNum_skills = _tblCallbackDataDetails.SeqNum_acdCallDetail
		WHERE _tblCallbackDataDetails.ServiceId_acdCallDetail IN (".$serviceid.") AND _tempTableLastIVRSkillset.Skill_Id IN (".$skillid.") and _tblCallbackDataDetails.SiteName in(".$sid.") order by DATE_TIME";
		$query = $this->db->query($cmd);	 
		return $query;
	}
	
	public function get_report_24($datefrom,$dateto,$site){
				$site = rtrim($site,",");
					$sites = explode(",",$site);
					$sid = "";
					foreach($sites as $sitelist){
						$sid .= "'".$sitelist."',";
					}
					$sid =  rtrim($sid,",");
					$cmd ="DECLARE @FromDateTime datetime
					DECLARE @ToDateTime datetime
					SET @FromDateTime = '".$datefrom."'
					SET @ToDateTime = '".$dateto."'
					SET NOCOUNT ON;
					With abandoned_base as (
					select	case when Param17 in ('Corporate Business Group', 'Home and Micro Business', 'SME Business Group')
					then Param17 else 'N/A'	end as Segment,acdCallDetail.CallStartDt,acdCallDetail.SeqNum,acdCallDetail.AgentDispId,acdCallDetail.Service_Id,acdCallDetail.CallActionId
					,case when IsNumeric(media.param15) = 1	then media.param15 else null end as dnis from REPDB..ACDCallDetail acdCallDetail, REPDB..MediaDataDetail media
					where acdCallDetail.SeqNum = media.SeqNum and 
					acdCallDetail.Service_Id in ('4000019', '4000020') and (acdCallDetail.CallActionId  in (5,6,18))
					and Format(acdCallDetail.CallStartDt, 'mm/dd/yyyy hh:mm') = Format(media.CallStartDt, 'mm/dd/yyyy hh:mm'))

					, abandoned_site as (
						SELECT a.*, isnull(s.SiteName, 'MOC') as dnis_site FROM abandoned_base a left join RepUIDB..DNIS d on a.DNIS = d.DNIS left join RepUIDB..Sites s on d.SiteGuid = s.SiteGuid
					)

					, agent_site_login as (
						select	l.LoginDt,l.LogoutDt,l.User_Id,s.Station,isnull(sites.SiteName,'MOC') as agent_site	from REPDB..AgentLoginLogout l
						left join REPDB..AgentStateAudit s on DATEADD(ms, -datepart(ms, l.LoginDt), l.LoginDt) = dateadd(ms, -datepart(ms, s.Status_Start_dt), s.Status_Start_dt)
						left join RepUIDB..Stations station on s.Station = station.Station left join RepUIDB..Sites sites on station.SiteGuid = sites.SiteGuid
						where s.Station is not null and (dateadd(hour, 8, l.LoginDt) between @FromDateTime and @ToDateTime or (dateadd(hour, 8, l.LogoutDt) between @FromDateTime and @ToDateTime or l.LogoutDt is null))
						and l.Service_Id = 0)


					,abandoned_agent_site as (
						select	ab.Segment,ab.AgentDispId,ab.Service_Id,ab.CallActionId,ab.callstartdt,ab.seqnum,ab.dnis,ab.dnis_site,isnull(agt.agent_site, 'MOC') as abandoned_site_tagging
						from abandoned_site ab	left join agent_site_login agt on (ab.callstartdt between agt.LoginDt and agt.LogoutDt or ab.callstartdt >= agt.LoginDt and agt.LogoutDt is null) and ab.dnis_site = agt.agent_site
					)

					, filtered_abandoned_agent_site as (
					select *, abandoned_site_tagging as SiteName from abandoned_agent_site	where dateadd(hour,8,CallStartDt) between @FromDateTime and @ToDateTime	and dnis_site in (".$sid.")
					)

					, base_abandoned_site as (
					select Segment, CallStartDt, SeqNum, AgentDispId, Service_Id, CallActionId, SiteName from filtered_abandoned_agent_site where CallActionId in (5,6)
					)

					, answered_calls_volume as (
					select case	when Param17 in ('Corporate Business Group', 'Home and Micro Business', 'SME Business Group')
					then Param17 else 'N/A'	end as Segment,acdCallDetail.CallStartDt,acdCallDetail.SeqNum,SiteName,acdCallDetail.AgentDispId,acdCallDetail.Service_Id,acdCallDetail.CallActionId
					from [REPDB].[dbo].[ACDCallDetail] acdCallDetail, [REPDB].[dbo].[MediaDataDetail] mediaDataDetail, [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.AgentState agentState,
					RepUIDB.dbo.Stations stations,  RepUIDB.dbo.Sites sites where  acdCallDetail.SeqNum = mediaDataDetail.SeqNum
					and acdCallDetail.User_Id = agentState.User_Id and  acdCallDetail.Station = stations.Station and  stations.SiteGuid= sites.SiteGuid
					and acdCallDetail.Service_Id in ('4000019', '4000020') and (acdCallDetail.CallActionId = 8)
					)

					, filtered_answered_calls_volume as (
						select * from answered_calls_volume	where dateadd(hour,8,answered_calls_volume.CallStartDt) between @FromDateTime and @ToDateTime and SiteName in (".$sid.")
					)

					, base_call_volume as (select Segment, CallStartDt, SeqNum, AgentDispId, Service_Id, CallActionId, SiteName from filtered_answered_calls_volume Union select * from base_abandoned_site)
					, base_segment as (	select * from filtered_answered_calls_volume)

					, count_per_concern_type as (
					select base_call_volume.Segment,base_call_volume.SiteName, count(distinct(base_call_volume.SeqNum))as [Total Calls], count(distinct(filtered_answered_calls_volume.SeqNum))as [Answered Calls]
					, count(distinct(base_abandoned_site.SeqNum))as [Abandoned Calls]
					,case when base_segment.AgentDispId in ('4000310','4000311','4000312','4000314','4000315') then count(distinct(base_segment.SeqNum)) end as [Accident To Public]
					,case when base_segment.AgentDispId in ('4000140','4000632','4000633','4000634','4000635','4000638') then count(distinct(base_segment.SeqNum)) end as [AMC]
					,case when base_segment.AgentDispId in ('4000643','4000644','4000648','4000657','4000641') then count(distinct(base_segment.SeqNum)) end as [Bayad Center - Availment Svc]
					,case when base_segment.AgentDispId in ('4000480','4000483','4000485','4000486','4000489') then count(distinct(base_segment.SeqNum)) end as [Bill Deposit Refund (Contract Terminated)]
					,case when base_segment.AgentDispId in ('4000723','4000726','4000728','4000730','4000732') then count(distinct(base_segment.SeqNum)) end as [Bill Duplicate Request]
					,case when base_segment.AgentDispId in ('4000414','4000416','4000418','4000419','4000421') then count(distinct(base_segment.SeqNum)) end as [Blurred Meter Glass]
					,case when base_segment.AgentDispId in ('4000432','4000434','4000440','4000441','4000443','4000884','4000885') then count(distinct(base_segment.SeqNum)) end as [Broken/ Missing Terminal Seal]
					,case when base_segment.AgentDispId in ('4000451','4000454','4000455','4000457','4000461') then count(distinct(base_segment.SeqNum)) end as [Cash Advance Refund]
					,case when base_segment.AgentDispId in ('4000328','4000329','4000330','4000331','4000332') then count(distinct(base_segment.SeqNum)) end as [Confinement (Off-Duty)]
					,case when base_segment.AgentDispId in ('4000304','4000305','4000306','4000307','4000308') then count(distinct(base_segment.SeqNum)) end as [Cust Svc Feedback]
					,case when base_segment.AgentDispId in ('4000343','4000346','4000348','4000349','4000351') then count(distinct(base_segment.SeqNum)) end as [Damage Pte Property]
					,case when base_segment.AgentDispId in ('4000235','4000236','4000237','4000238','4000239') then count(distinct(base_segment.SeqNum)) end as [Disconnected with Consumption]
					,case when base_segment.AgentDispId in ('4000355','4000356','4000357','4000358','4000359') then count(distinct(base_segment.SeqNum)) end as [Electrically Shocked]
					,case when base_segment.AgentDispId in ('4000893','4000892','4000242') then count(distinct(base_segment.SeqNum)) end as [Electronic Bill Availability]
					,case when base_segment.AgentDispId in ('4000833','4000834','4000835','4000836','4000837','4000894','4000895') then count(distinct(base_segment.SeqNum)) end as [Energy Conservation Tips]
					,case when base_segment.AgentDispId in ('4000838','4000246','4000247','4000248','4000249') then count(distinct(base_segment.SeqNum)) end as [Erroneous APA Cancellation]
					,case when base_segment.AgentDispId in ('4000255','4000256','4000257','4000258','4000259') then count(distinct(base_segment.SeqNum)) end as [Erroneous AWA Tagging]
					,case when base_segment.AgentDispId in ('4000225','4000226','4000227','4000228','4000229') then count(distinct(base_segment.SeqNum)) end as [Erroneous Contract Data]
					,case when base_segment.AgentDispId in ('4000265','4000266','4000267','4000268','4000269') then count(distinct(base_segment.SeqNum)) end as [Erroneous Pymt Post]
					,case when base_segment.AgentDispId in ('4000752','4000753','4000754','4000755','4000756') then count(distinct(base_segment.SeqNum)) end as [Est Dep Amt Inq]
					,case when base_segment.AgentDispId in ('4000874','4000271','4000272','4000273','4000274') then count(distinct(base_segment.SeqNum)) end as [Flat Streetlight High Billing]
					,case when base_segment.AgentDispId in ('4000778','4000779','4000780','4000781','4000782') then count(distinct(base_segment.SeqNum)) end as [Inq Svc Reading Date]
					,case when base_segment.AgentDispId in ('4000552','4000554','4000555','4000556','4000558') then count(distinct(base_segment.SeqNum)) end as [Interruptible Load Prog]
					,case when base_segment.AgentDispId in ('4000400','4000402','4000405','4000407','4000409') then count(distinct(base_segment.SeqNum)) end as [Line Conductor Cover Instl]
					,case when base_segment.AgentDispId in ('4000494','4000496','4000497','4000499','4000501') then count(distinct(base_segment.SeqNum)) end as [Meter Dirt Inside]
					,case when base_segment.AgentDispId in ('4000321','4000322','4000324','4000326','4000327') then count(distinct(base_segment.SeqNum)) end as [Admin Case]
					,case when base_segment.AgentDispId in ('4000854','4000855','4000856','4000857','4000858') then count(distinct(base_segment.SeqNum)) end as [Bal Inq]
					,case when base_segment.AgentDispId in ('4000883','4000231','4000232','4000233','4000234','4000768') then count(distinct(base_segment.SeqNum)) end as [Bill & Pay Notif]
					,case when base_segment.AgentDispId in ('4000465','4000467','4000469','4000471','4000476') then count(distinct(base_segment.SeqNum)) end as [Bill Deposit Refund (Good Credit Standing)]
					,case when base_segment.AgentDispId in ('4000767','4000769','4000770','4000771','4000772') then count(distinct(base_segment.SeqNum)) end as [Bill Presentment Process]
					,case when base_segment.AgentDispId in ('4000423','4000424','4000426','4000428','4000430') then count(distinct(base_segment.SeqNum)) end as [Broken Meter Glass Cover]
					,case when base_segment.AgentDispId in ('4000446','4000448','4000450','4000452','4000453') then count(distinct(base_segment.SeqNum)) end as [Burnt Meter]
					,case when base_segment.AgentDispId in ('4000594','4000596','4000598','4000599','4000601') then count(distinct(base_segment.SeqNum)) end as [Clawback & Rates]
					,case when base_segment.AgentDispId in ('4000844','4000845','4000846','4000847','4000848') then count(distinct(base_segment.SeqNum)) end as [Contact Info Svc Offices]
					,case when base_segment.AgentDispId in ('4000335','4000337','4000340','4000341','4000342') then count(distinct(base_segment.SeqNum)) end as [Damage Co Property]
					,case when base_segment.AgentDispId in ('4000886','4000887') then count(distinct(base_segment.SeqNum)) end as [Damage to Public Property]
					,case when base_segment.AgentDispId in ('4000773','4000774','4000775','4000776','4000777','4000890','4000891') then count(distinct(base_segment.SeqNum)) end as [Delq Mgmt Status]
					,case when base_segment.AgentDispId in ('4000463','4000464','4000466','4000468','4000470') then count(distinct(base_segment.SeqNum)) end as [Dial Pointers Out of Alignment]
					,case when base_segment.AgentDispId in ('4000747','4000748','4000749','4000750','4000751') then count(distinct(base_segment.SeqNum)) end as [Doc Reqs Inq]
					,case when base_segment.AgentDispId in ('4000373','4000374','4000375','4000377','4000379') then count(distinct(base_segment.SeqNum)) end as [Electrocution]
					,case when base_segment.AgentDispId in ('4000381','4000383','4000385','4000386','4000387') then count(distinct(base_segment.SeqNum)) end as [Employee Accident]
					,case when base_segment.AgentDispId in ('4000413','4000415','4000417','4000420','4000422') then count(distinct(base_segment.SeqNum)) end as [Enrollment to ILP & Update]
					,case when base_segment.AgentDispId in ('4000250','4000251','4000252','4000253','4000254') then count(distinct(base_segment.SeqNum)) end as [Erroneous APA Enrollment]
					,case when base_segment.AgentDispId in ('4000260','4000261','4000262','4000263','4000264') then count(distinct(base_segment.SeqNum)) end as [Erroneous BA Bill]
					,case when base_segment.AgentDispId in ('4000602','4000603','4000604','4000606','4000608') then count(distinct(base_segment.SeqNum)) end as [Erroneous Deduction of Load]
					,case when base_segment.AgentDispId in ('4000720','4000724','4000725','4000727','4000729') then count(distinct(base_segment.SeqNum)) end as [Erroneous SI Bill]
					,case when base_segment.AgentDispId in ('4000472','4000473','4000474','4000475','4000477','4000898','4000899') then count(distinct(base_segment.SeqNum)) end as [Fast/ Slow Meter]
					,case when base_segment.AgentDispId in ('4000275','4000276','4000277','4000278','4000279') then count(distinct(base_segment.SeqNum)) end as [High Billing]
					,case when base_segment.AgentDispId in ('4000783','4000784','4000785','4000786','4000787') then count(distinct(base_segment.SeqNum)) end as [Inq Bill / DN Delivery]
					,case when base_segment.AgentDispId in ('4000793','4000794','4000795','4000796','4000797') then count(distinct(base_segment.SeqNum)) end as [Inq Bill Computation]
					,case when base_segment.AgentDispId in ('4000798','4000799','4000800','4000801','4000802') then count(distinct(base_segment.SeqNum)) end as [Inq Consump]
					,case when base_segment.AgentDispId in ('4000808','4000809','4000810','4000811','4000812') then count(distinct(base_segment.SeqNum)) end as [Inq Payment]
					,case when base_segment.AgentDispId in ('4000478','4000479','4000481','4000482','4000484') then count(distinct(base_segment.SeqNum)) end as [Intchg Meter]
					,case when base_segment.AgentDispId in ('4000731','4000733','4000734','4000735','4000736') then count(distinct(base_segment.SeqNum)) end as [KWatch Report]
					,case when base_segment.AgentDispId in ('4000388','4000390','4000392','4000394','4000396') then count(distinct(base_segment.SeqNum)) end as [Line Conductor Cover Removal]
					,case when base_segment.AgentDispId in ('4000904','4000905') then count(distinct(base_segment.SeqNum)) end as [Meralco Online and Mobile App]
					,case when base_segment.AgentDispId in ('4000456','4000458','4000459','4000460','4000462','4000888','4000889') then count(distinct(base_segment.SeqNum)) end as [Deformed/ Detached Parts Inside Meter]
					,case when base_segment.AgentDispId in ('4000712','4000713','4000714','4000716','4000717') then count(distinct(base_segment.SeqNum)) end as [Denial of Violation]
					,case when base_segment.AgentDispId in ('4000757','4000758','4000759','4000760','4000761') then count(distinct(base_segment.SeqNum)) end as [Inq App New Svc]
					,case when base_segment.AgentDispId in ('4000788','4000789','4000790','4000791','4000792') then count(distinct(base_segment.SeqNum)) end as [Inq Bill Amt and Bill Details]
					,case when base_segment.AgentDispId in ('4000839','4000840','4000841','4000842','4000843') then count(distinct(base_segment.SeqNum))end as [Inq Concern Status]
					,case when base_segment.AgentDispId in ('4000803','4000804','4000805','4000806','4000807','4000901','4000902') then count(distinct(base_segment.SeqNum)) end as [Inq Delq Mgmt Process]
					,case when base_segment.AgentDispId in ('4000280','4000281','4000282','4000283','4000284') then count(distinct(base_segment.SeqNum)) end as [Low Billing]
					,case when base_segment.AgentDispId in ('4000900','4000903') then count(distinct(base_segment.SeqNum)) end as [Meralco Online and Mobile App Mod]
					,case when base_segment.AgentDispId in ('4000442','4000444','4000445','4000447','4000449') then count(distinct(base_segment.SeqNum)) end as [Meter Deposit Refund]
					,case when base_segment.AgentDispId in ('4000503','4000504','4000505','4000506','4000508') then count(distinct(base_segment.SeqNum)) end as [Meter No Display]
					,case when base_segment.AgentDispId in ('4000661','4000663','4000664','4000665','4000670') then count(distinct(base_segment.SeqNum)) end as [MIESCOR - Svc Avail]
					,case when base_segment.AgentDispId in ('4000672','4000674','4000675','4000677','4000678') then count(distinct(base_segment.SeqNum)) end as [MSERV- Svc Avail]
					,case when base_segment.AgentDispId in ('4000136','4000611','4000613','4000614','4000616','4000618') then count(distinct(base_segment.SeqNum)) end as [No Notification Received For Top-Up]
					,case when base_segment.AgentDispId in ('4000680','4000682','4000683','4000685','4000686') then count(distinct(base_segment.SeqNum)) end as [Non-AMC - Svc Avail]
					,case when base_segment.AgentDispId in ('4000124','4000487','4000488','4000490','4000491','4000492') then count(distinct(base_segment.SeqNum)) end as [Meter Running Even w/o Load]
					,case when base_segment.AgentDispId in ('4000376','4000378','4000380','4000382','4000384') then count(distinct(base_segment.SeqNum)) end as [Misc]
					,case when base_segment.AgentDispId in ('4000859','4000860','4000861','4000862','4000863') then count(distinct(base_segment.SeqNum)) end as [Net Metering]
					,case when base_segment.AgentDispId in ('4000620','4000621','4000622','4000624','4000626') then count(distinct(base_segment.SeqNum)) end as [No Power with Load]
					,case when base_segment.AgentDispId in ('4000131','4000562','4000564','4000570','4000571','4000572') then count(distinct(base_segment.SeqNum)) end as [Not Satisfied With Energy Walkthrough Audit]
					,case when base_segment.AgentDispId in ('4000576','4000577','4000579','4000581','4000582') then count(distinct(base_segment.SeqNum)) end as [Not Satisfied with Power Quality Walkthrough Audit]
					,case when base_segment.AgentDispId in ('4000640','4000636','4000637','4000639','4000642') then count(distinct(base_segment.SeqNum)) end as [Others - Req Eqpt Rent]
					,case when base_segment.AgentDispId in ('4000742','4000743','4000744','4000745','4000746') then count(distinct(base_segment.SeqNum)) end as [Outage-Related Queries]
					,case when base_segment.AgentDispId in ('4000389','4000391','4000393','4000395','4000397') then count(distinct(base_segment.SeqNum)) end as [Pilferage (Energy)]
					,case when base_segment.AgentDispId in ('4000864','4000865','4000866','4000867','4000868') then count(distinct(base_segment.SeqNum)) end as [POP]
					,case when base_segment.AgentDispId in ('4000818','4000819','4000820','4000821','4000822') then count(distinct(base_segment.SeqNum)) end as [Pymt Process]
					,case when base_segment.AgentDispId in ('4000687','4000689','4000690','4000692','4000694') then count(distinct(base_segment.SeqNum)) end as [RADIUS - Svc Avail]
					,case when base_segment.AgentDispId in ('4000368','4000369','4000370','4000371','4000372') then count(distinct(base_segment.SeqNum)) end as [RCOA - Desist DC]
					,case when base_segment.AgentDispId in ('4000615','4000617','4000619','4000623','4000625') then count(distinct(base_segment.SeqNum)) end as [RCOA - Req Duplicate COC]
					,case when base_segment.AgentDispId in ('4000585','4000586','4000588','4000590','4000592') then count(distinct(base_segment.SeqNum)) end as [RCOA- Meter Data Prov]
					,case when base_segment.AgentDispId in ('4000425','4000427','4000429','4000431','4000433') then count(distinct(base_segment.SeqNum)) end as [Refund Meter Deposit]
					,case when base_segment.AgentDispId in ('4000697','4000698','4000699','4000701','4000704') then count(distinct(base_segment.SeqNum)) end as [Republic Surety - Svc Avail]
					,case when base_segment.AgentDispId in ('4000715','4000718','4000719','4000721','4000722') then count(distinct(base_segment.SeqNum)) end as [Req Bill History]
					,case when base_segment.AgentDispId in ('4000605','4000607','4000609','4000610','4000612') then count(distinct(base_segment.SeqNum)) end as [Req Cert Bill Deposit]
					,case when base_segment.AgentDispId in ('4000583','4000584','4000587','4000589','4000591') then count(distinct(base_segment.SeqNum)) end as [Req Doc Copy]
					,case when base_segment.AgentDispId in ('4000333','4000334','4000336','4000338','4000339') then count(distinct(base_segment.SeqNum)) end as [Req Exc PI]
					,case when base_segment.AgentDispId in ('4000533','4000534','4000535','4000536','4000537') then count(distinct(base_segment.SeqNum)) end as [Req IPA ]
					,case when base_segment.AgentDispId in ('4000688','4000691','4000693','4000695','4000696') then count(distinct(base_segment.SeqNum)) end as [Req Meter Reading Sched]
					,case when base_segment.AgentDispId in ('4000666','4000667','4000668','4000669','4000671') then count(distinct(base_segment.SeqNum)) end as [Req Meter Test]
					,case when base_segment.AgentDispId in ('4000219','4000344','4000345','4000347','4000350','4000352') then count(distinct(base_segment.SeqNum)) end as [Req PI History Copy]
					,case when base_segment.AgentDispId in ('4000318','4000319','4000320','4000323','4000325') then count(distinct(base_segment.SeqNum)) end as [Req Postponement PI]
					,case when base_segment.AgentDispId in ('4000309','4000241','4000313','4000316','4000317') then count(distinct(base_segment.SeqNum)) end as [Req Rubber Hose Intall]
					,case when base_segment.AgentDispId in ('4000656','4000658','4000659','4000660','4000662') then count(distinct(base_segment.SeqNum)) end as [Req Special Meter Reading]
					,case when base_segment.AgentDispId in ('4000651','4000652','4000653','4000654','4000655') then count(distinct(base_segment.SeqNum)) end as [Req Witness Meter Reading]
					,case when base_segment.AgentDispId in ('4000737','4000738','4000739','4000740','4000741') then count(distinct(base_segment.SeqNum)) end as [Sched Pre-Arranged Interruption]
					,case when base_segment.AgentDispId in ('4000509','4000511','4000513','4000515','4000517') then count(distinct(base_segment.SeqNum)) end as [Stolen Meter]
					,case when base_segment.AgentDispId in ('4000762','4000763','4000764','4000765','4000766') then count(distinct(base_segment.SeqNum)) end as [Svc App Status Inq]
					,case when base_segment.AgentDispId in ('4000627','4000628','4000629','4000630','4000631') then count(distinct(base_segment.SeqNum)) end as [Unable Top-up]
					,case when base_segment.AgentDispId in ('4000290','4000291','4000292','4000293','4000295') then count(distinct(base_segment.SeqNum)) end as [Unposted Pymt]
					,case when base_segment.AgentDispId in ('4000297','4000298','4000299','4000300','4000301') then count(distinct(base_segment.SeqNum)) end as [Vacant Consumption]
					,case when base_segment.AgentDispId in ('4000538','4000539','4000541','4000546','4000548') then count(distinct(base_segment.SeqNum)) end as [Violation DPA]
					,case when base_segment.AgentDispId in ('4000879','4000880','4000881','4000882') then count(distinct(base_segment.SeqNum)) end as [OTHERS]
					,case when base_segment.AgentDispId in ('4000645','4000646','4000647','4000650','4000649') then count(distinct(base_segment.SeqNum)) end as [Others - Req Mechl/Chem Test]
					,case when base_segment.AgentDispId in ('4000813','4000814','4000815','4000816','4000817') then count(distinct(base_segment.SeqNum)) end as [Paperless Subs&Cancel]
					,case when base_segment.AgentDispId in ('4000398','4000399','4000401','4000403','4000404') then count(distinct(base_segment.SeqNum)) end as [Pilferage (Property)]
					,case when base_segment.AgentDispId in ('4000869','4000870','4000871','4000872','4000873') then count(distinct(base_segment.SeqNum)) end as [Prepaid Svc]
					,case when base_segment.AgentDispId in ('4000823','4000824','4000825','4000826','4000827') then count(distinct(base_segment.SeqNum)) end as [Pymt Status]
					,case when base_segment.AgentDispId in ('4000363','4000364','4000365','4000366','4000367') then count(distinct(base_segment.SeqNum)) end as [RCOA - DC]
					,case when base_segment.AgentDispId in ('4000897','4000896') then count(distinct(base_segment.SeqNum)) end as [RCOA - Reconnection]
					,case when base_segment.AgentDispId in ('4000700','4000702','4000703','4000705','4000706') then count(distinct(base_segment.SeqNum)) end as [RCOA - Special Meter Reading]
					,case when base_segment.AgentDispId in ('4000435','4000436','4000437','4000438','4000439') then count(distinct(base_segment.SeqNum)) end as [Refund Credit Acct]
					,case when base_segment.AgentDispId in ('4000849','4000850','4000851','4000852','4000853') then count(distinct(base_segment.SeqNum)) end as [Report Bug]
					,case when base_segment.AgentDispId in ('4000218','4000353','4000354','4000360','4000876','4000362','4000361') then count(distinct(base_segment.SeqNum)) end as [Req Assist Maint Cust Facil]
					,case when base_segment.AgentDispId in ('4000547','4000549','4000550','4000551','4000553') then count(distinct(base_segment.SeqNum)) end as [Req Billing Adjust Disc]
					,case when base_segment.AgentDispId in ('4000593','4000595','4000597','4000600','4000877') then count(distinct(base_segment.SeqNum)) end as [Req Clean Transformer Vault]
					,case when base_segment.AgentDispId in ('4000565','4000566','4000567','4000568','4000569') then count(distinct(base_segment.SeqNum)) end as [Req Escort Svc]
					,case when base_segment.AgentDispId in ('4000540','4000542','4000543','4000544','4000545') then count(distinct(base_segment.SeqNum)) end as [Req Inclusion IPA to APA]
					,case when base_segment.AgentDispId in ('4000573','4000574','4000575','4000578','4000580') then count(distinct(base_segment.SeqNum)) end as [Req Load Profile Copy]
					,case when base_segment.AgentDispId in ('4000673','4000676','4000679','4000681','4000684') then count(distinct(base_segment.SeqNum)) end as [Req Meter Replacement]
					,case when base_segment.AgentDispId in ('4000532','4000528','4000529','4000530','4000531','4000875') then count(distinct(base_segment.SeqNum)) end as [Req OR]
					,case when base_segment.AgentDispId in ('4000557','4000559','4000560','4000561','4000563') then count(distinct(base_segment.SeqNum)) end as [Req POP Simulation Comparison]
					,case when base_segment.AgentDispId in ('4000507','4000510','4000512','4000514','4000516') then count(distinct(base_segment.SeqNum)) end as [Req Prepaid Loading History]
					,case when base_segment.AgentDispId in ('4000523','4000524','4000525','4000526','4000527') then count(distinct(base_segment.SeqNum)) end as [Req SI Discount]
					,case when base_segment.AgentDispId in ('4000244','4000302','4000303','4000240','4000243') then count(distinct(base_segment.SeqNum)) end as [Req Temp PI]
					,case when base_segment.AgentDispId in ('4000828','4000829','4000830','4000831','4000832') then count(distinct(base_segment.SeqNum)) end as [Sched / Bill Status / DN Delivery]
					,case when base_segment.AgentDispId in ('4000707','4000708','4000709','4000710','4000711') then count(distinct(base_segment.SeqNum)) end as [SPECTRUM - Svc Avail]
					,case when base_segment.AgentDispId in ('4000518','4000519','4000520','4000521','4000522') then count(distinct(base_segment.SeqNum)) end as [Stopped Meter]
					,case when base_segment.AgentDispId in ('4000270','4000294','4000296','4000230','4000287') then count(distinct(base_segment.SeqNum)) end as [Test]
					,case when base_segment.AgentDispId in ('4000285','4000286','4000878','4000288','4000289') then count(distinct(base_segment.SeqNum)) end as [Undelivered Bill]
					,case when base_segment.AgentDispId in ('4000493','4000495','4000498','4000500','4000502') then count(distinct(base_segment.SeqNum)) end as [Update Acct Details]
					,case when base_segment.AgentDispId in ('4000406','4000408','4000410','4000411','4000412') then count(distinct(base_segment.SeqNum)) end as [Vehicular Accident]
					from base_call_volume
					left join base_segment on base_call_volume.SeqNum = base_segment.SeqNum
					left join filtered_answered_calls_volume on base_call_volume.SeqNum = filtered_answered_calls_volume.SeqNum
					left join base_abandoned_site on base_call_volume.SeqNum = base_abandoned_site.SeqNum
					group by base_call_volume.Segment, base_segment.AgentDispId, base_call_volume.SiteName)

					, raw_total_segment_per_concern_type as (
					select Segment,SiteName,ISNULL(sum([Total Calls]),0) as [Total Calls],ISNULL(sum([Answered Calls]),0) as [Answered Calls],ISNULL(sum([Abandoned Calls]),0) as [Abandoned Calls],ISNULL(sum([Accident To Public]),0) as [Accident To Public]
					,ISNULL(sum([AMC]),0) as [AMC],ISNULL(sum([Bayad Center - Availment Svc]),0) as [Bayad Center - Availment Svc],ISNULL(sum([Bill Deposit Refund (Contract Terminated)]),0) as [Bill Deposit Refund (Contract Terminated)]
					,ISNULL(sum([Bill Duplicate Request]),0) as [Bill Duplicate Request],ISNULL(sum([Blurred Meter Glass]),0) as [Blurred Meter Glass],ISNULL(sum([Broken/ Missing Terminal Seal]),0) as [Broken/ Missing Terminal Seal]
					,ISNULL(sum([Cash Advance Refund]),0) as [Cash Advance Refund],ISNULL(sum([Confinement (Off-Duty)]),0) as [Confinement (Off-Duty)],ISNULL(sum([Cust Svc Feedback]),0) as [Cust Svc Feedback]
					,ISNULL(sum([Damage Pte Property]),0) as [Damage Pte Property],ISNULL(sum([Disconnected with Consumption]),0) as [Disconnected with Consumption],ISNULL(sum([Electrically Shocked]),0) as [Electrically Shocked]
					,ISNULL(sum([Electronic Bill Availability]),0) as [Electronic Bill Availability],ISNULL(sum([Energy Conservation Tips]),0) as [Energy Conservation Tips],ISNULL(sum([Erroneous APA Cancellation]),0) as [Erroneous APA Cancellation]
					,ISNULL(sum([Erroneous AWA Tagging]),0) as [Erroneous AWA Tagging],ISNULL(sum([Erroneous Contract Data]),0) as [Erroneous Contract Data],ISNULL(sum([Erroneous Pymt Post]),0) as [Erroneous Pymt Post]
					,ISNULL(sum([Est Dep Amt Inq]),0) as [Est Dep Amt Inq],ISNULL(sum([Flat Streetlight High Billing]),0) as [Flat Streetlight High Billing],ISNULL(sum([Inq Svc Reading Date]),0) as [Inq Svc Reading Date]
					,ISNULL(sum([Interruptible Load Prog]),0) as [Interruptible Load Prog],ISNULL(sum([Line Conductor Cover Instl]),0) as [Line Conductor Cover Instl],ISNULL(sum([Meter Dirt Inside]),0) as [Meter Dirt Inside]
					,ISNULL(sum([Admin Case]),0) as [Admin Case],ISNULL(sum([Bal Inq]),0) as [Bal Inq],ISNULL(sum([Bill & Pay Notif]),0) as [Bill & Pay Notif]
					,ISNULL(sum([Bill Deposit Refund (Good Credit Standing)]),0) as [Bill Deposit Refund (Good Credit Standing)],ISNULL(sum([Bill Presentment Process]),0) as [Bill Presentment Process]
					,ISNULL(sum([Broken Meter Glass Cover]),0) as [Broken Meter Glass Cover],ISNULL(sum([Burnt Meter]),0) as [Burnt Meter],ISNULL(sum([Clawback & Rates]),0) as [Clawback & Rates]
					,ISNULL(sum([Contact Info Svc Offices]),0) as [Contact Info Svc Offices],ISNULL(sum([Damage Co Property]),0) as [Damage Co Property],ISNULL(sum([Damage to Public Property]),0) as [Damage to Public Property]
					,ISNULL(sum([Delq Mgmt Status]),0) as [Delq Mgmt Status],ISNULL(sum([Dial Pointers Out of Alignment]),0) as [Dial Pointers Out of Alignment],ISNULL(sum([Doc Reqs Inq]),0) as [Doc Reqs Inq]
					,ISNULL(sum([Electrocution]),0) as [Electrocution],ISNULL(sum([Employee Accident]),0) as [Employee Accident],ISNULL(sum([Enrollment to ILP & Update]),0) as [Enrollment to ILP & Update]
					,ISNULL(sum([Erroneous APA Enrollment]),0) as [Erroneous APA Enrollment],ISNULL(sum([Erroneous BA Bill]),0) as [Erroneous BA Bill],ISNULL(sum([Erroneous Deduction of Load]),0) as [Erroneous Deduction of Load]
					,ISNULL(sum([Erroneous SI Bill]),0) as [Erroneous SI Bill],ISNULL(sum([Fast/ Slow Meter]),0) as [Fast/ Slow Meter],ISNULL(sum([High Billing]),0) as [High Billing]
					,ISNULL(sum([Inq Bill / DN Delivery]),0) as [Inq Bill / DN Delivery],ISNULL(sum([Inq Bill Computation]),0) as [Inq Bill Computation],ISNULL(sum([Inq Consump]),0) as [Inq Consump]
					,ISNULL(sum([Inq Payment]),0) as [Inq Payment],ISNULL(sum([Intchg Meter]),0) as [Intchg Meter],ISNULL(sum([KWatch Report]),0) as [KWatch Report],ISNULL(sum([Line Conductor Cover Removal]),0) as [Line Conductor Cover Removal]
					,ISNULL(sum([Meralco Online and Mobile App]),0) as [Meralco Online and Mobile App],ISNULL(sum([Deformed/ Detached Parts Inside Meter]),0) as [Deformed/ Detached Parts Inside Meter]
					,ISNULL(sum([Denial of Violation]),0) as [Denial of Violation],ISNULL(sum([Inq App New Svc]),0) as [Inq App New Svc],ISNULL(sum([Inq Bill Amt and Bill Details]),0) as [Inq Bill Amt and Bill Details]
					,ISNULL(sum([Inq Concern Status]),0) as [Inq Concern Status],ISNULL(sum([Inq Delq Mgmt Process]),0) as [Inq Delq Mgmt Process],ISNULL(sum([Low Billing]),0) as [Low Billing]
					,ISNULL(sum([Meralco Online and Mobile App Mod]),0) as [Meralco Online and Mobile App Mod],ISNULL(sum([Meter Deposit Refund]),0) as [Meter Deposit Refund]
					,ISNULL(sum([Meter No Display]),0) as [Meter No Display],ISNULL(sum([MIESCOR - Svc Avail]),0) as [MIESCOR - Svc Avail],ISNULL(sum([MSERV- Svc Avail]),0) as [MSERV- Svc Avail]
					,ISNULL(sum([No Notification Received For Top-Up]),0) as [No Notification Received For Top-Up],ISNULL(sum([Non-AMC - Svc Avail]),0) as [Non-AMC - Svc Avail]
					,ISNULL(sum([Meter Running Even w/o Load]),0) as [Meter Running Even w/o Load],ISNULL(sum([Misc]),0) as [Misc],ISNULL(sum([Net Metering]),0) as [Net Metering]
					,ISNULL(sum([No Power with Load]),0) as [No Power with Load],ISNULL(sum([Not Satisfied With Energy Walkthrough Audit]),0) as [Not Satisfied With Energy Walkthrough Audit]
					,ISNULL(sum([Not Satisfied with Power Quality Walkthrough Audit]),0) as [Not Satisfied with Power Quality Walkthrough Audit],ISNULL(sum([Others - Req Eqpt Rent]),0) as [Others - Req Eqpt Rent]
					,ISNULL(sum([Outage-Related Queries]),0) as [Outage-Related Queries],ISNULL(sum([Pilferage (Energy)]),0) as [Pilferage (Energy)],ISNULL(sum([POP]),0) as [POP],ISNULL(sum([Pymt Process]),0) as [Pymt Process]
					,ISNULL(sum([RADIUS - Svc Avail]),0) as [RADIUS - Svc Avail],ISNULL(sum([RCOA - Desist DC]),0) as [RCOA - Desist DC],ISNULL(sum([RCOA - Req Duplicate COC]),0) as [RCOA - Req Duplicate COC]
					,ISNULL(sum([RCOA- Meter Data Prov]),0) as [RCOA- Meter Data Prov],ISNULL(sum([Refund Meter Deposit]),0) as [Refund Meter Deposit],ISNULL(sum([Republic Surety - Svc Avail]),0) as [Republic Surety - Svc Avail]
					,ISNULL(sum([Req Bill History]),0) as [Req Bill History],ISNULL(sum([Req Cert Bill Deposit]),0) as [Req Cert Bill Deposit],ISNULL(sum([Req Doc Copy]),0) as [Req Doc Copy]
					,ISNULL(sum([Req Exc PI]),0) as [Req Exc PI],ISNULL(sum([Req IPA ]),0) as [Req IPA ],ISNULL(sum([Req Meter Reading Sched]),0) as [Req Meter Reading Sched]
					,ISNULL(sum([Req Meter Test]),0) as [Req Meter Test],ISNULL(sum([Req PI History Copy]),0) as [Req PI History Copy],ISNULL(sum([Req Postponement PI]),0) as [Req Postponement PI]
					,ISNULL(sum([Req Rubber Hose Intall]),0) as [Req Rubber Hose Intall],ISNULL(sum([Req Special Meter Reading]),0) as [Req Special Meter Reading]
					,ISNULL(sum([Req Witness Meter Reading]),0) as [Req Witness Meter Reading],ISNULL(sum([Sched Pre-Arranged Interruption]),0) as [Sched Pre-Arranged Interruption]
					,ISNULL(sum([Stolen Meter]),0) as [Stolen Meter],ISNULL(sum([Svc App Status Inq]),0) as [Svc App Status Inq]
					,ISNULL(sum([Unable Top-up]),0) as [Unable Top-up],ISNULL(sum([Unposted Pymt]),0) as [Unposted Pymt],ISNULL(sum([Vacant Consumption]),0) as [Vacant Consumption]
					,ISNULL(sum([Violation DPA]),0) as [Violation DPA],ISNULL(sum([OTHERS]),0) as [OTHERS],ISNULL(sum([Others - Req Mechl/Chem Test]),0) as [Others - Req Mechl/Chem Test]
					,ISNULL(sum([Paperless Subs&Cancel]),0) as [Paperless Subs&Cancel],ISNULL(sum([Pilferage (Property)]),0) as [Pilferage (Property)],ISNULL(sum([Prepaid Svc]),0) as [Prepaid Svc]
					,ISNULL(sum([Pymt Status]),0) as [Pymt Status],ISNULL(sum([RCOA - DC]),0) as [RCOA - DC],ISNULL(sum([RCOA - Reconnection]),0) as [RCOA - Reconnection]
					,ISNULL(sum([RCOA - Special Meter Reading]),0) as [RCOA - Special Meter Reading],ISNULL(sum([Refund Credit Acct]),0) as [Refund Credit Acct]
					,ISNULL(sum([Report Bug]),0) as [Report Bug],ISNULL(sum([Req Assist Maint Cust Facil]),0) as [Req Assist Maint Cust Facil],ISNULL(sum([Req Billing Adjust Disc]),0) as [Req Billing Adjust Disc]
					,ISNULL(sum([Req Clean Transformer Vault]),0) as [Req Clean Transformer Vault],ISNULL(sum([Req Escort Svc]),0) as [Req Escort Svc]
					,ISNULL(sum([Req Inclusion IPA to APA]),0) as [Req Inclusion IPA to APA],ISNULL(sum([Req Load Profile Copy]),0) as [Req Load Profile Copy]
					,ISNULL(sum([Req Meter Replacement]),0) as [Req Meter Replacement],ISNULL(sum([Req OR]),0) as [Req OR]
					,ISNULL(sum([Req POP Simulation Comparison]),0) as [Req POP Simulation Comparison],ISNULL(sum([Req Prepaid Loading History]),0) as [Req Prepaid Loading History]
					,ISNULL(sum([Req SI Discount]),0) as [Req SI Discount],ISNULL(sum([Req Temp PI]),0) as [Req Temp PI],ISNULL(sum([Sched / Bill Status / DN Delivery]),0) as [Sched / Bill Status / DN Delivery]
					,ISNULL(sum([SPECTRUM - Svc Avail]),0) as [SPECTRUM - Svc Avail],ISNULL(sum([Stopped Meter]),0) as [Stopped Meter],ISNULL(sum([Test]),0) as [Test]
					,ISNULL(sum([Undelivered Bill]),0) as [Undelivered Bill],ISNULL(sum([Update Acct Details]),0) as [Update Acct Details],ISNULL(sum([Vehicular Accident]),0) as [Vehicular Accident]
					from count_per_concern_type	group by Segment, SiteName )

					select * from raw_total_segment_per_concern_type where SiteName IN (".$sid.")";

					$query = $this->db->query($cmd);	 	
					return $query;
	}
	
	public function get_report_21a($df,$dt,$tlx,$att){
		$attx = explode(",",$att);
		$xx = "";
		foreach($attx as $x){
			$xx .="'".$x."',";
		}
		$att = rtrim($xx,",");
		$tlxx = explode(",",$tlx);
		$xx = "";
		foreach($tlxx as $x){
			$xx .="'".$x."',";
		}
		$tlx = rtrim($xx,",");
					
			$cmd ="
			DECLARE	@FromDateTime datetime,	@ToDateTime datetime2,	@Telex_Ids varchar(20),	@Attendant_Ids varchar(20);
			SET @FromDateTime = '".$df."';
			SET @ToDateTime = '".$dt."';
			SET NOCOUNT ON;

			DECLARE @tmp21_tableA table (AGENT_NAME VARCHAR(50),DATE VARCHAR(50),TIME_INTERVAL VARCHAR(50),TIME_GRAPH VARCHAR(50),DateTimeInterval datetime,ANSWERED_CALLS_OFFERED int,ANSWERED_TOTAL_DURATION float,
				ANSWERED_AVG_DURATION float,ANSWERED_TOTAL_TIME_TO_ANSWER float,ANSWERED_AVG_TIME_TO_ANSWER float,ABANDONED_CALLS_OFFERED INT,ABANDONED_TOTAL_DURATION float,ABANDONED_AVG_DURATION float,
				ABANDONED_TOTAL_TIME_TO_ABANDON float,ABANDONED_AVG_TIME_TO_ABANDON float,CALLSTARTDT DATETIME,[15MinsInterval] datetime,[30MinsInterval] datetime,	[60MinsInterval] datetime,
				Sunday varchar(50),	Saturday varchar(50),Minus15Mins varchar(50),Minus30Mins varchar(50),Minus60Mins varchar(50),Week_Count int,Day_Count int,	Day_Name varchar(50),perDate varchar(50),
				Month_Count int,MONTH_NAME varchar(50),	Year_Month varchar(50),	YEAR int,DATE_TIME datetime,Service_Shift varchar(50),Detail_Hour varchar(50),Detail_15Mins varchar(50),Detail_30Mins varchar(50))
			INSERT INTO @tmp21_tableA
			SELECT CO_AgentName as AGENT_NAME, FORMAT(Call_Start_Date_Time, 'yyyy-MM-dd') as DATE, FORMAT(Call_Start_Date_Time, 'hh:00tt') as TIME_INTERVAL,FORMAT(dateadd(hour, 12,Call_Start_Date_Time), 'hh:00') as TIME_GRAPH,
			DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, call_start_date_time)) / 60 * 60, 0) as DateTimeInterval,
				 sum(isnull((CASE WHEN CO_Disposition = 'Handled' Then 1 END),0)) as ANSWERED_CALLS_OFFERED,
				 CAST((sum(isnull((CASE WHEN CO_Disposition = 'Handled' Then Call_Connected_Time END),0))) as decimal(18,2))/60 as ANSWERED_TOTAL_DURATION,
				 CAST((isnull(CASE WHEN CO_Disposition = 'Handled' Then Call_Duration END,0)) as decimal(18))/60 as ANSWERED_AVG_DURATION,
				 CAST(SUM(isnull(CASE WHEN CO_Disposition = 'Handled' Then CO_Duration END,0)) as decimal(18))/60 as ANSWERED_TOTAL_TIME_TO_ANSWER,
				 CAST(AVG(isnull(CASE WHEN CO_Disposition = 'Handled' Then CO_Duration END,0)) as decimal(18))/60 as ANSWERED_AVG_TIME_TO_ANSWER,
				 COUNT(isnull(CASE WHEN CO_Disposition = 'Abandoned' Then 1 END,0)) as ABANDONED_CALLS_OFFERED,
				 CAST(SUM(isnull(CASE WHEN CO_Disposition = 'Abandoned' Then 0 END,0)) as decimal(18))/60 as ABANDONED_TOTAL_DURATION,
				 CAST(AVG(isnull(CASE WHEN CO_Disposition = 'Abandoned' Then 0 END,0)) as decimal(18))/60 as ABANDONED_AVG_DURATION,
				 CAST(SUM(isnull(CASE WHEN CO_Disposition = 'Abandoned' Then CO_Duration END,0)) as decimal(18))/60 as ABANDONED_TOTAL_TIME_TO_ABANDON,
				 CAST(AVG(isnull(CASE WHEN CO_Disposition = 'Abandoned' Then CO_Duration END,0)) as decimal(18))/60 as ABANDONED_AVG_TIME_TO_ABANDON,
				DATEADD(HOUR, 0,Call_Start_Date_Time) AS CALLSTARTDT,
				DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 15, Call_Start_Date_Time)) / 15 * 15, 0)) as \"15MinsInterval\",
				DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 30, Call_Start_Date_Time)) / 30 * 30, 0)) as \"30MinsInterval\",
				DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 60, Call_Start_Date_Time)) / 60 * 60, 0)) as \"60MinsInterval\",
				CONVERT(VARCHAR(20), (dateadd(dd, 0 - (@@datefirst +6 + datepart(dw, dateadd(hour, 8,Call_Start_Date_Time))) %7 , dateadd(hour, 8,Call_Start_Date_Time))), 101) as Sunday,
				CONVERT(VARCHAR(20), (dateadd(dd, 6 - (@@datefirst +6 + datepart(dw, dateadd(hour, 8, Call_Start_Date_Time))) %7 , dateadd(hour, 8,Call_Start_Date_Time))), 101) as Saturday,
				convert(varchar(10), dateadd(hour, 0,Call_Start_Date_Time), 101) + ' ' +
				format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Call_Start_Date_Time)) / 15 * 15, 0)) as Datetime), 'hh:mm tt') as Minus15Mins,
				convert(varchar(10), dateadd(hour, 0,Call_Start_Date_Time), 101) + ' ' +
				format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Call_Start_Date_Time)) / 30 * 30, 0)) as Datetime), 'hh:mm tt') as Minus30Mins,
				convert(varchar(10), dateadd(hour, 0,Call_Start_Date_Time), 101) + ' ' +
				format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Call_Start_Date_Time)) / 60 * 60, 0)) as Datetime), 'hh:mm tt') as Minus60Mins,
				datepart(wk, dateadd(hour, 0, Call_Start_Date_Time)) as Week_count,datepart(w, dateadd(hour, 0, Call_Start_Date_Time)) as Day_Count, 
				datename(dw, dateadd(hour, 0, Call_Start_Date_Time)) as Day_Name,convert(varchar(10), dateadd(hour, 0,Call_Start_Date_Time), 101) as perDate,
				DATEPART(M, DATEADD(HOUR, 0, Call_Start_Date_Time)) AS Month_Count,
				cast(datename(m, dateadd(hour,0,Call_Start_Date_Time)) as varchar(10)) + ', ' + cast(year(dateadd(hour,0,Call_Start_Date_Time)) as varchar(10)) AS MONTH_NAME,
				cast(year(dateadd(hour,0,Call_Start_Date_Time)) as varchar(10)) + ', ' + cast(datename(m, dateadd(hour,0,Call_Start_Date_Time)) as varchar(10)) as Year_Month,
				CAST(YEAR(DATEADD(HOUR, 0, Call_Start_Date_Time)) AS VARCHAR)AS YEAR,DATEADD(HOUR, 0, Call_Start_Date_Time) AS DATE_TIME,
				convert(varchar(10), dateadd(hour, 0,Call_Start_Date_Time), 101) +' '+
				case when datepart(hour, dateadd(hour, 0, Call_Start_Date_Time)) <= 5 or datepart(hour, dateadd(hour, 0, Call_Start_Date_Time)) >= 22 then '10PM - 6AM'
				when datepart(hour, dateadd(hour, 0, Call_Start_Date_Time)) > = 14 and datepart(hour, dateadd(hour, 0, Call_Start_Date_Time)) <= 21 then '2PM - 10PM'
				when datepart(hour, dateadd(hour, 0, Call_Start_Date_Time)) >= 6 and datepart(hour, dateadd(hour, 0, Call_Start_Date_Time)) <= 13 then '6AM - 2PM' end as Service_Shift,
				format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Call_Start_Date_Time)) / 60 * 60, 0)) as Datetime), 'hh:mm tt') as Detail_Hour,
				format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Call_Start_Date_Time)) / 15 * 15, 0)) as Datetime), 'hh:mm tt') as Detail_15Mins,
				format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Call_Start_Date_Time)) / 30 * 30, 0)) as Datetime), 'hh:mm tt') as Detail_30Mins
			FROM
				 (Select cd2.[Call] as Call,cd1.[Start_Date_Time] as Call_Start_Date_Time,cd1.[Call_Duration] as Call_Duration,cd1.[Alerting_Time] as Call_Alerting_Time,
						cd1.[Connected_Time] as Call_Connected_Time,cd1.[Call_Origin] as Call_Origin,cd1.[CLI] as Calling_Party,cd1.[Redirected_Device] as Redirected_Party,
						cd1.[Completion_Code] as Call_Disposition,cd2.[Call_Seq] as Call_Seq,cd2.[Event_Code] as Event_Code,cd2.[Agent_Unique_Ref] as CO_AgentID,
						ad.[Full_Name] as CO_AgentName,	(CAST(CAST(cd2.[Duration] as decimal(18,8)) / 1000 as decimal(18,4))) as CO_Duration,
						LAG(cd2.[Event_Code],1 ) OVER (ORDER BY cd2.[Call], cd2.[Call_Seq]) as Prev_Event_Code, LEAD (cd2.[Event_Code], 1) OVER (ORDER BY cd2.[Call], cd2.[Call_Seq]) as Next_Event_Code,
						CASE WHEN LAG(cd2.[Event_Code],1 ) OVER (ORDER BY cd2.[Call], cd2.[Call_Seq]) <> 'PARK' AND LEAD (cd2.[Event_Code], 1) OVER (ORDER BY cd2.[Call], cd2.[Call_Seq]) = 'ACAPT' THEN 'Handled'
							 WHEN LAG(cd2.[Event_Code],1 ) OVER (ORDER BY cd2.[Call], cd2.[Call_Seq]) <> 'PARK' AND LEAD (cd2.[Event_Code], 1) OVER (ORDER BY cd2.[Call], cd2.[Call_Seq]) = 'ACWDN' THEN 'Abandoned'
							 WHEN LAG(cd2.[Event_Code],1 ) OVER (ORDER BY cd2.[Call], cd2.[Call_Seq]) <> 'PARK' AND LEAD (cd2.[Event_Code], 1) OVER (ORDER BY cd2.[Call], cd2.[Call_Seq]) = 'DCONN' THEN 'Abandoned'
							 ELSE 'NULL' END as CO_Disposition
				 FROM   [MOCCUACPUB01].[ATTLOG].[dbo].[Call_Details_001] as cd1 RIGHT JOIN [MOCCUACPUB01].[ATTLOG].[dbo].[Call_Details_002] as cd2 ON cd1.[Call] = cd2.[Call]
						LEFT JOIN [MOCCUACPUB01].[ATTLOG].[dbo].[Agent_Details] as ad ON ad.[Agent_Unique_Ref] = cd2.[Agent_Unique_Ref] WHERE cd1.Call_Type = 'I'
						AND cd1.[Arrival_Queue_Unique_Ref] <> 'NULL' AND cd1.DDI IN (".$tlx.")) a
				WHERE [Event_Code] = 'ACOFF'  AND CO_Disposition <> 'NULL' AND Call_Start_Date_Time >= @FromDateTime AND Call_Start_Date_Time <= @ToDateTime AND CO_AgentName IN (".$att.")
				GROUP BY CO_AgentName,
					 FORMAT(Call_Start_Date_Time, 'yyyy-MM-dd'),
					 FORMAT(Call_Start_Date_Time, 'hh:00tt'),
					FORMAT(dateadd(hour, 12,Call_Start_Date_Time), 'hh:00'),
					DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, call_start_date_time)) / 60 * 60, 0) ,
					DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 15, Call_Start_Date_Time)) / 15 * 15, 0)),
					DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 30, Call_Start_Date_Time)) / 30 * 30, 0)),
					CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 60, Call_Start_Date_Time)) / 60 * 60, 0)) AS DATETIME),
					CONVERT(VARCHAR(20), (dateadd(dd, 0 - (@@datefirst +6 + datepart(dw, dateadd(hour, 8,Call_Start_Date_Time))) %7 , dateadd(hour, 8,Call_Start_Date_Time))), 101),
					CONVERT(VARCHAR(20), (dateadd(dd, 6 - (@@datefirst +6 + datepart(dw, dateadd(hour, 8, Call_Start_Date_Time))) %7 , dateadd(hour, 8,Call_Start_Date_Time))), 101),
					datepart(wk, dateadd(hour, 0, Call_Start_Date_Time)),datepart(w, dateadd(hour, 0, Call_Start_Date_Time)),datename(dw, dateadd(hour, 0, Call_Start_Date_Time)),
					convert(varchar(10), dateadd(hour, 0,Call_Start_Date_Time), 101),DATEPART(M, DATEADD(HOUR, 0, Call_Start_Date_Time)),
					cast(datename(m, dateadd(hour,0,Call_Start_Date_Time)) as varchar(10)) + ', ' + cast(year(dateadd(hour,8,Call_Start_Date_Time)) as varchar(10)),
					cast(year(dateadd(hour,0,Call_Start_Date_Time)) as varchar(10)) + ', ' + cast(datename(m, dateadd(hour,8,Call_Start_Date_Time)) as varchar(10)),
					YEAR(DATEADD(HOUR, 0, Call_Start_Date_Time)),DATEADD(HOUR, 0, Call_Start_Date_Time),
					CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Call_Start_Date_Time)) / 60 * 60, 0)) as Datetime),
					format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Call_Start_Date_Time)) / 15 * 15, 0)) as Datetime), 'hh:mm tt'),
					format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Call_Start_Date_Time)) / 30 * 30, 0)) as Datetime), 'hh:mm tt') ,
					format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Call_Start_Date_Time)) / 60 * 60, 0)) as Datetime), 'hh:mm tt'),
					convert(varchar(10), dateadd(hour, 0,Call_Start_Date_Time), 101) +' '+
						case when datepart(hour, dateadd(hour, 0, Call_Start_Date_Time)) <= 5 or datepart(hour, dateadd(hour, 0, Call_Start_Date_Time)) >= 22 then '10PM - 6AM'
					when datepart(hour, dateadd(hour, 0, Call_Start_Date_Time)) > = 14 and datepart(hour, dateadd(hour, 0, Call_Start_Date_Time)) <= 21 then '2PM - 10PM'
					when datepart(hour, dateadd(hour, 0, Call_Start_Date_Time)) >= 6 and datepart(hour, dateadd(hour, 0, Call_Start_Date_Time)) <= 13 then '6AM - 2PM' end,
					format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Call_Start_Date_Time)) / 60 * 60, 0)) as Datetime), 'hh:mm tt'),
					Format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Call_Start_Date_Time)) / 60 * 60, 0)) as Datetime), 'hh:mm tt'),
					format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Call_Start_Date_Time)) / 30 * 30, 0)) as Datetime), 'hh:mm tt'),
					format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Call_Start_Date_Time)) / 15 * 15, 0)) as Datetime), 'hh:mm tt'),Call_Duration,CO_Disposition

			DECLARE @BaseCursor CURSOR
			DECLARE @tmpInterval datetime
			DECLARE @tmpOperator varchar(50)

			SET @BaseCursor = CURSOR FOR
			SELECT DISTINCT DATEADD(MINUTE, DATEDIFF(MINUTE, 15, DATEADD(MINUTE, 0, [15MinsInterval])) / 15 * 15, 0), AGENT_NAME FROM @tmp21_tableA

			OPEN @BaseCursor
			FETCH NEXT
			FROM @BaseCursor INTO @tmpInterval, @tmpOperator
			WHILE @@FETCH_STATUS = 0
			BEGIN
			INSERT INTO @tmp21_tableA (AGENT_NAME, DATE, TIME_INTERVAL, DateTimeInterval, [15MinsInterval] ,[30MinsInterval] ,[60MinsInterval] ,Sunday ,Saturday ,Minus15Mins ,Minus30Mins,
				Minus60Mins ,Week_Count ,Day_Count ,Day_Name ,perDate ,Month_Count ,MONTH_NAME ,Year_Month ,YEAR ,DATE_TIME, Service_Shift, Detail_hour, Detail_15Mins, Detail_30Mins)
				SELECT @tmpOperator,FORMAT(BASE.Interval, 'yyyy-MM-dd') as DATE,FORMAT(BASE.Interval, 'hh:00tt') as TIME_INTERVAL,
				DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, BASE.Interval)) / 60 * 60, 0) as DateTimeInterval,
				DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 15, BASE.Interval)) / 15 * 15, 0)) as \"15MinsInterval\",
				DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 30, BASE.Interval)) / 30 * 30, 0)) as \"30MinsInterval\",
				DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 60, BASE.Interval)) / 60 * 60, 0)) as \"60MinsInterval\",
				CONVERT(VARCHAR(20), (dateadd(dd, 0 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,BASE.Interval))) %7 , dateadd(hour, 0,BASE.Interval))), 101) as Sunday,
				CONVERT(VARCHAR(20), (dateadd(dd, 6 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,BASE.Interval))) %7 , dateadd(hour, 0,BASE.Interval))), 101) as Saturday,
				convert(varchar(10), dateadd(hour, 0,BASE.Interval), 101) + ' ' +
				format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, BASE.Interval)) / 15 * 15, 0)) as Datetime), 'hh:mm tt') as Minus15Mins,
				convert(varchar(10), dateadd(hour, 0,BASE.Interval), 101) + ' ' +
				format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, BASE.Interval)) / 30 * 30, 0)) as Datetime), 'hh:mm tt') as Minus30Mins,
				convert(varchar(10), dateadd(hour, 0,BASE.Interval), 101) + ' ' +
				format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, BASE.Interval)) / 60 * 60, 0)) as Datetime), 'hh:mm tt') as Minus60Mins,
				datepart(wk, dateadd(hour, 0, BASE.Interval)) as Week_count,datepart(w, dateadd(hour, 0, BASE.Interval)) as Day_Count, 
				datename(dw, dateadd(hour, 0, BASE.Interval)) as Day_Name,	convert(varchar(10), dateadd(hour, 0,BASE.Interval), 101) as perDate,
				DATEPART(M, DATEADD(HOUR, 0, BASE.Interval)) AS Month_Count,cast(datename(m, dateadd(hour,0,BASE.Interval)) as varchar(10)) + ', ' + cast(year(dateadd(hour,0,BASE.Interval)) as varchar(10)) AS MONTH_NAME,
				cast(year(dateadd(hour,0,BASE.Interval)) as varchar(10)) + ', ' + cast(datename(m, dateadd(hour,0,BASE.Interval)) as varchar(10)) as Year_Month,
				YEAR(DATEADD(HOUR, 0, BASE.Interval)) AS YEAR,DATEADD(HOUR, 0, BASE.Interval) AS DATE_TIME,
				convert(varchar(10), dateadd(hour, 0,base.Interval), 101) +' '+	case when datepart(hour, dateadd(hour, 0,base.Interval)) <= 5 or datepart(hour, dateadd(hour, 0,base.Interval)) >= 22 then '10PM - 6AM'
				when datepart(hour, dateadd(hour, 0,base.Interval)) > = 14 and datepart(hour, dateadd(hour, 0,base.Interval)) <= 21 then '2PM - 10PM'
				when datepart(hour, dateadd(hour, 0,base.Interval)) >= 6 and datepart(hour, dateadd(hour, 0,base.Interval)) <= 13 then '6AM - 2PM' end as Service_Shift,
				format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, base.Interval)) / 60 * 60, 0)) as Datetime), 'hh:mm tt'),
				format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, base.Interval)) / 15 * 15, 0)) as Datetime), 'hh:mm tt'),
				format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, base.Interval)) / 30 * 30, 0)) as Datetime), 'hh:mm tt')
				FROM [REPDB].[dbo].[_base15mtable] BASE	WHERE Base.[Interval] BETWEEN @FromDateTime AND @ToDateTime
				AND NOT EXISTS (SELECT * FROM @tmp21_tableA B WHERE b.[15MinsInterval] = BASE.Interval AND b.AGENT_NAME = @tmpOperator)
				FETCH NEXT                                                                                                                                                                                                                                                                          
				FROM @BaseCursor INTO @tmpInterval, @tmpOperator
			END

			CLOSE @BaseCursor
			DEALLOCATE @BaseCursor 
			
			SELECT * FROM @tmp21_tableA	ORDER BY [15MinsInterval] ";
			$query = $this->db->query($cmd);	 
			return $query;
	}
	
	public function get_report_21b($df,$dt,$tlx,$att){
		$attx = explode(",",$att);
		$xx = "";
		foreach($attx as $x){
			$xx .="'".$x."',";
		}
		$att = rtrim($xx,",");
			$tlxx = explode(",",$tlx);
		$xx = "";
		foreach($tlxx as $x){
			$xx .="'".$x."',";
		}
		$tlx = rtrim($xx,",");
					
			$cmd ="DECLARE @FromDateTime datetime,@ToDateTime datetime2;
			SET @FromDateTime = '".$df."';
			SET @ToDateTime = '".$dt."';
			SET NOCOUNT ON;

			DECLARE @tmp21_tableB table (AGENT_NAME VARCHAR(50),DATE VARCHAR(50),TIME_INTERVAL VARCHAR(50),DateTimeInterval datetime,INTERNAL_CALLS_OFFERED FLOAT,INTERNAL_TOTAL_DURATION FLOAT,INTERNAL_AVG_DURATION FLOAT,
				INTERNAL_TOTAL_TIME_TO_ANSWER FLOAT,INTERNAL_AVG_TIME_TO_ANSWER FLOAT,EXTERNAL_CALLS_OFFERED FLOAT,	EXTERNAL_TOTAL_DURATION FLOAT,EXTERNAL_AVG_DURATION FLOAT,
				EXTERNAL_TOTAL_TIME_TO_ABANDON FLOAT,EXTERNAL_AVG_TIME_TO_ABANDON FLOAT,CALLSTARTDT DATETIME,[15MinsInterval] datetime,[30MinsInterval] datetime,[60MinsInterval] datetime,
				Sunday varchar(50),	Saturday varchar(50),Minus15Mins varchar(50),Minus30Mins varchar(50),Minus60Mins varchar(50),Week_Count int,Day_Count int,	Day_Name varchar(50),
				perDate varchar(50),Month_Count int,MONTH_NAME varchar(50),	Year_Month varchar(50),YEAR int,DATE_TIME datetime,	Service_Shift varchar(50),Detail_Hour varchar(50),Detail_15Mins varchar(50),Detail_30Mins varchar(50));

			with baseTableB as (Select cd2.[Call] as Call,cd1.[Start_Date_Time] as Call_Start_Date_Time,cd1.[Call_Duration] as Call_Duration,cd1.[Alerting_Time] as Call_Alerting_Time,cd1.[Connected_Time] as Call_Connected_Time,
							cd1.[Call_Origin] as Call_Origin,cd1.[CLI] as Calling_Party,cd1.Completion_Code,cd1.[Redirected_Device] as Redirected_Party,cd1.[Completion_Code] as Call_Disposition,
							cd2.[Call_Seq] as Call_Seq,	cd2.[Event_Code] as Event_Code,	cd2.[Agent_Unique_Ref] as CO_AgentID,ad.[Full_Name] as CO_AgentName,(CAST(CAST(cd2.[Duration] as decimal(18,8)) / 1000 as decimal(18,4))) as CO_Duration,
							LAG(cd2.[Event_Code],1 ) OVER (ORDER BY cd2.[Call], cd2.[Call_Seq]) as Prev_Event_Code,LEAD (cd2.[Event_Code], 1) OVER (ORDER BY cd2.[Call], cd2.[Call_Seq]) as Next_Event_Code,
							CASE WHEN LAG(cd2.[Event_Code],1 ) OVER (ORDER BY cd2.[Call], cd2.[Call_Seq]) <> 'PARK' AND LEAD (cd2.[Event_Code], 1) OVER (ORDER BY cd2.[Call], cd2.[Call_Seq]) = 'ACAPT' THEN 'Handled'
								 WHEN LAG(cd2.[Event_Code],1 ) OVER (ORDER BY cd2.[Call], cd2.[Call_Seq]) <> 'PARK' AND LEAD (cd2.[Event_Code], 1) OVER (ORDER BY cd2.[Call], cd2.[Call_Seq]) = 'ACWDN' THEN 'EXTERNAL'
								 WHEN LAG(cd2.[Event_Code],1 ) OVER (ORDER BY cd2.[Call], cd2.[Call_Seq]) <> 'PARK' AND LEAD (cd2.[Event_Code], 1) OVER (ORDER BY cd2.[Call], cd2.[Call_Seq]) = 'DCONN' THEN 'EXTERNAL'
								 ELSE 'NULL' END as CO_Disposition
					 FROM   [MOCCUACPUB01].[ATTLOG].[dbo].[Call_Details_001] as cd1	RIGHT JOIN [MOCCUACPUB01].[ATTLOG].[dbo].[Call_Details_002] as cd2 ON cd1.[Call] = cd2.[Call] LEFT JOIN [MOCCUACPUB01].[ATTLOG].[dbo].[Agent_Details] as ad ON
								ad.[Agent_Unique_Ref] = cd2.[Agent_Unique_Ref] WHERE cd1.Call_Type = 'I' AND cd1.[Arrival_Queue_Unique_Ref] <> 'NULL' AND cd1.DDI IN (".$tlx."))
			, baseTableBFiltered as (SELECT * FROM baseTableB WHERE [Event_Code] = 'ACOFF'  AND CO_Disposition IS NOT NULL  AND Call_Start_Date_Time >= @FromDateTime AND Call_Start_Date_Time <= @ToDateTime  AND CO_AgentName IN (".$att."))
			, final_query as (
				Select CO_AgentName as AGENT_NAME, FORMAT(Call_Start_Date_Time, 'yyyy-MM-dd') as DATE, FORMAT(Call_Start_Date_Time, 'hh:00tt') as TIME_INTERVAL,DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 60, call_start_date_time)) / 60 * 60, 0) as DateTimeInterval,
				SUM(isnull((CASE WHEN CO_Disposition = 'Handled' AND Call_Origin = 'COINT' Then 1 END),0)) as INTERNAL_CALLS_OFFERED,
				 CAST(SUM(isnull(CASE WHEN CO_Disposition = 'Handled' and Call_Origin = 'COINT' AND [Completion_Code] = 'ACOMP' OR [Completion_Code] = 'RCOMP' Then Call_Connected_Time END,0))as decimal(18))/60 as INTERNAL_TOTAL_DURATION,
				 CAST(AVG(isnull(CASE WHEN CO_Disposition = 'Handled' and Call_Origin = 'COINT' AND [Completion_Code] = 'ACOMP' OR [Completion_Code] = 'RCOMP' Then Call_Connected_Time END,0)) as decimal(18))/60 as INTERNAL_AVG_DURATION,
				 CAST(SUM(isnull(CASE WHEN CO_Disposition = 'Handled' and Call_Origin = 'COINT' AND [Completion_Code] = 'ACOMP' OR [Completion_Code] = 'RCOMP' Then CO_Duration END,0)) as decimal(18))/60 as INTERNAL_TOTAL_TIME_TO_ANSWER,
				 CAST(AVG(isnull(CASE WHEN Call_Origin = 'COINT' AND CO_Disposition = 'Handled'  Then CO_Duration END,0)) as decimal(18))/60 as INTERNAL_AVG_TIME_TO_ANSWER,
				 SUM(isnull(CASE WHEN CO_Disposition = 'Handled' AND Call_Origin = 'COEXT' Then 1 END,0)) as EXTERNAL_CALLS_OFFERED,
				 CAST(SUM(isnull(CASE WHEN CO_Disposition = 'Handled' and Call_Origin = 'COEXT' AND [Completion_Code] = 'ACOMP' OR [Completion_Code] = 'RCOMP'  Then Call_Connected_Time END,0)) as decimal(18))/60 as EXTERNAL_TOTAL_DURATION,
				 CAST(AVG(isnull(CASE WHEN CO_Disposition = 'Handled' and Call_Origin = 'COEXT' AND [Completion_Code] = 'ACOMP' OR [Completion_Code] = 'RCOMP' Then Call_Connected_Time END,0)) as decimal(18))/60 as EXTERNAL_AVG_DURATION,
				 CAST(SUM(isnull(CASE WHEN CO_Disposition = 'Handled' and Call_Origin = 'COEXT' AND [Completion_Code] = 'ACOMP' OR [Completion_Code] = 'RCOMP' Then CO_Duration END,0)) as decimal(18))/60 as EXTERNAL_TOTAL_TIME_TO_ABANDON,
				 CAST(AVG(isnull(CASE WHEN CO_Disposition = 'Handled' and Call_Origin = 'COEXT' AND CO_Disposition = 'Handled'  Then CO_Duration END,0)) as decimal(18))/60 as EXTERNAL_AVG_TIME_TO_ABANDON,
				DATEADD(HOUR, 0,Call_Start_Date_Time) AS CALLSTARTDT,DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 15, Call_Start_Date_Time)) / 15 * 15, 0)) as \"15MinsInterval\",
				DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 30, Call_Start_Date_Time)) / 30 * 30, 0)) as \"30MinsInterval\",
				DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 60, Call_Start_Date_Time)) / 60 * 60, 0)) as \"60MinsInterval\",
				CONVERT(VARCHAR(20), (dateadd(dd, 0 - (@@datefirst +6 + datepart(dw, dateadd(hour, 8,Call_Start_Date_Time))) %7 , dateadd(hour, 8,Call_Start_Date_Time))), 101) as Sunday,
				CONVERT(VARCHAR(20), (dateadd(dd, 6 - (@@datefirst +6 + datepart(dw, dateadd(hour, 8,Call_Start_Date_Time))) %7 , dateadd(hour, 8,Call_Start_Date_Time))), 101) as Saturday,
				convert(varchar(10), dateadd(hour, 0,Call_Start_Date_Time), 101) + ' ' +
				format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Call_Start_Date_Time)) / 15 * 15, 0)) as Datetime), 'hh:mm tt') as Minus15Mins,
				convert(varchar(10), dateadd(hour, 0,Call_Start_Date_Time), 101) + ' ' +
				format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Call_Start_Date_Time)) / 30 * 30, 0)) as Datetime), 'hh:mm tt') as Minus30Mins,
				convert(varchar(10), dateadd(hour, 0,Call_Start_Date_Time), 101) + ' ' +
				format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Call_Start_Date_Time)) / 60 * 60, 0)) as Datetime), 'hh:mm tt') as Minus60Mins,
				datepart(wk, dateadd(hour, 0, Call_Start_Date_Time)) as Week_count,datepart(w, dateadd(hour, 0, Call_Start_Date_Time)) as Day_Count, datename(dw, dateadd(hour, 0, Call_Start_Date_Time)) as Day_Name,
				convert(varchar(10), dateadd(hour, 0,Call_Start_Date_Time), 101) as perDate,DATEPART(M, DATEADD(HOUR, 0, Call_Start_Date_Time)) AS Month_Count,
				cast(datename(m, dateadd(hour,0,Call_Start_Date_Time)) as varchar(10)) + ', ' + cast(year(dateadd(hour,0,Call_Start_Date_Time)) as varchar(10)) AS MONTH_NAME,
				cast(year(dateadd(hour,0,Call_Start_Date_Time)) as varchar(10)) + ', ' + cast(datename(m, dateadd(hour,0,Call_Start_Date_Time)) as varchar(10)) as Year_Month,
				CAST(YEAR(DATEADD(HOUR, 0, Call_Start_Date_Time)) AS VARCHAR) AS YEAR,DATEADD(HOUR, 0, Call_Start_Date_Time) AS DATE_TIME,
				convert(varchar(10), dateadd(hour, 0,Call_Start_Date_Time), 101) +' '+
				case when datepart(hour, dateadd(hour, 0, Call_Start_Date_Time)) <= 5 or datepart(hour, dateadd(hour, 0, Call_Start_Date_Time)) >= 22 then '10PM - 6AM'
				when datepart(hour, dateadd(hour, 0, Call_Start_Date_Time)) > = 14 and datepart(hour, dateadd(hour, 0, Call_Start_Date_Time)) <= 21 then '2PM - 10PM'
				when datepart(hour, dateadd(hour, 0, Call_Start_Date_Time)) >= 6 and datepart(hour, dateadd(hour, 0, Call_Start_Date_Time)) <= 13 then '6AM - 2PM' end
				as Service_Shift,format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Call_Start_Date_Time)) / 15 * 15, 0)) as Datetime), 'hh:mm tt') as Detail_15Mins,
				format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Call_Start_Date_Time)) / 30 * 30, 0)) as Datetime), 'hh:mm tt') as Detail_30Mins,
				format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Call_Start_Date_Time)) / 60 * 60, 0)) as Datetime), 'hh:mm tt') as Detail_Hour
				from baseTableBFiltered	group by CO_AgentName,Call_Start_Date_Time)
				
				INSERT INTO @tmp21_tableB select * from final_query

				DECLARE @BaseCursor CURSOR
				DECLARE @tmpInterval datetime
				DECLARE @tmpOperator varchar(50)

				SET @BaseCursor = CURSOR FOR
				SELECT DISTINCT DATEADD(MINUTE, DATEDIFF(MINUTE, 15, DATEADD(MINUTE, 0, [15MinsInterval])) / 15 * 15, 0), AGENT_NAME FROM @tmp21_tableB

				OPEN @BaseCursor
				FETCH NEXT
				FROM @BaseCursor INTO @tmpInterval, @tmpOperator
				WHILE @@FETCH_STATUS = 0
				BEGIN
				INSERT INTO @tmp21_tableB (AGENT_NAME, DATE, TIME_INTERVAL, DateTimeInterval, [15MinsInterval] ,[30MinsInterval] ,[60MinsInterval] ,Sunday ,Saturday ,Minus15Mins ,Minus30Mins,
				Minus60Mins ,Week_Count ,Day_Count ,Day_Name ,perDate ,Month_Count ,MONTH_NAME ,Year_Month ,YEAR ,DATE_TIME, Service_Shift, Detail_hour, Detail_15Mins, Detail_30Mins)
				SELECT @tmpOperator,FORMAT(BASE.Interval, 'yyyy-MM-dd') as DATE,FORMAT(BASE.Interval, 'hh:00tt') as TIME_INTERVAL,
				DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, BASE.Interval)) / 60 * 60, 0) as DateTimeInterval,
				DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 15, BASE.Interval)) / 15 * 15, 0)) as \"15MinsInterval\",
				DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 30, BASE.Interval)) / 30 * 30, 0)) as \"30MinsInterval\",
				DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 60, BASE.Interval)) / 60 * 60, 0)) as \"60MinsInterval\",
				CONVERT(VARCHAR(20), (dateadd(dd, 0 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,BASE.Interval))) %7 , dateadd(hour, 0,BASE.Interval))), 101) as Sunday,
				CONVERT(VARCHAR(20), (dateadd(dd, 6 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,BASE.Interval))) %7 , dateadd(hour, 0,BASE.Interval))), 101) as Saturday,
				convert(varchar(10), dateadd(hour, 0,BASE.Interval), 101) + ' ' +
				format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, BASE.Interval)) / 15 * 15, 0)) as Datetime), 'hh:mm tt') as Minus15Mins,
				convert(varchar(10), dateadd(hour, 0,BASE.Interval), 101) + ' ' +
				format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, BASE.Interval)) / 30 * 30, 0)) as Datetime), 'hh:mm tt') as Minus30Mins,
				convert(varchar(10), dateadd(hour, 0,BASE.Interval), 101) + ' ' +
				format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, BASE.Interval)) / 60 * 60, 0)) as Datetime), 'hh:mm tt') as Minus60Mins,
				datepart(wk, dateadd(hour, 0, BASE.Interval)) as Week_count,datepart(w, dateadd(hour, 0, BASE.Interval)) as Day_Count, datename(dw, dateadd(hour, 0, BASE.Interval)) as Day_Name,
				convert(varchar(10), dateadd(hour, 0,BASE.Interval), 101) as perDate,DATEPART(M, DATEADD(HOUR, 0, BASE.Interval)) AS Month_Count,
				cast(datename(m, dateadd(hour,0,BASE.Interval)) as varchar(10)) + ', ' + cast(year(dateadd(hour,0,BASE.Interval)) as varchar(10)) AS MONTH_NAME,
				cast(year(dateadd(hour,0,BASE.Interval)) as varchar(10)) + ', ' + cast(datename(m, dateadd(hour,0,BASE.Interval)) as varchar(10)) as Year_Month,
				YEAR(DATEADD(HOUR, 0, BASE.Interval)) AS YEAR,DATEADD(HOUR, 0, BASE.Interval) AS DATE_TIME,	convert(varchar(10), dateadd(hour, 0,base.Interval), 101) +' '+
				case when datepart(hour, dateadd(hour, 0,base.Interval)) <= 5 or datepart(hour, dateadd(hour, 0,base.Interval)) >= 22 then '10PM - 6AM'
				when datepart(hour, dateadd(hour, 0,base.Interval)) > = 14 and datepart(hour, dateadd(hour, 0,base.Interval)) <= 21 then '2PM - 10PM'
				when datepart(hour, dateadd(hour, 0,base.Interval)) >= 6 and datepart(hour, dateadd(hour, 0,base.Interval)) <= 13 then '6AM - 2PM' end as Service_Shift,
				format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, base.Interval)) / 60 * 60, 0)) as Datetime), 'hh:mm tt'),
				format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, base.Interval)) / 15 * 15, 0)) as Datetime), 'hh:mm tt'),
				format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, base.Interval)) / 30 * 30, 0)) as Datetime), 'hh:mm tt')
				FROM [REPDB].[dbo].[_base15mtable] BASE	WHERE Base.[Interval] BETWEEN @FromDateTime AND @ToDateTime
				AND NOT EXISTS (SELECT * FROM @tmp21_tableB B WHERE b.[15MinsInterval] = BASE.Interval AND b.AGENT_NAME = @tmpOperator)

				FETCH NEXT                                                                                                                                                                                                                                                                          
				FROM @BaseCursor INTO @tmpInterval, @tmpOperator
			END

			CLOSE @BaseCursor
			DEALLOCATE @BaseCursor 

			SELECT * FROM @tmp21_tableB	ORDER BY [15MinsInterval]";		
			$query = $this->db->query($cmd);	 
			return $query;
	}
	
	public function getAgent_Skills($datefrom,$dateto){
		$cmd = "EXEC [REPDB].dbo.spvhis01AgentLoginLogout  '".$datefrom."','".$dateto."'";
		$query = $this->db->query($cmd);
	    $output = $query->result();
		return $query;
	}

	public function getAgents($not_in = ""){
		if($not_in == "") {
			$cmd = "exec [REPUIDB].[dbo].[spGetAspectAgents]";
		} else {
			$cmd = "SELECT A.[User_Id], U.[User_F_Name], U.[User_L_Name], (U.[User_F_Name] + ' ' + U.[User_L_Name]) as Fullname
					  FROM [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Agent] A LEFT JOIN [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].Users U ON A.[User_Id] = U.[User_Id]  WHERE U.[User_Id] NOT IN(".$not_in.")";
		}
		$query = $this->db->query($cmd);
		return $query;
	}
	
		public function getAgents2(){
		$cmd = "exec [REPUIDB].[dbo].[spGetAspectAgentsAlter]";
		$query = $this->db->query($cmd);
		return $query;
	}
	
	public function getAgents3(){
		$cmd = "exec [REPUIDB].[dbo].[GetSupervisorsAgents]";
		$query = $this->db->query($cmd);
		return $query;
	}
	
	public function getServices(){
		$cmd = "exec [REPUIDB].[dbo].[spGetAspectServices]";
		$query = $this->db->query($cmd);
		return $query;
	}
	
	public function getSupervisor(){
		$cmd = "exec [REPUIDB].[dbo].[spGetSupervisors]";
		$query = $this->db->query($cmd);
		return $query;
	}	

	public function getSkillsets(){
		$cmd = "exec [REPUIDB].[dbo].[spGetAspectSkillsets]";
		$query = $this->db->query($cmd);
		return $query;
	}

	public function get_folders_report($folderid) {
		$cmd = "SELECT [FolderReportGuid], [MyReportName] FROM [RepUIDB].[dbo].[FolderReports] WHERE [FolderGuid] = '".$folderid."' and [IsDeleted] = 0	ORDER by [CreatedDateTime]";
		$query = $this->db->query($cmd);
		return $query->result();
	}
	
	public function get_parent_folders() {
		$cmd = "SELECT [FolderGuid],[FolderName] FROM [RepUIDB].[dbo].[Folders] WHERE [IsDeleted] = 0 and [ParentGuid] IS NULL ORDER by [CreatedDateTime]";
		$query = $this->db->query($cmd);
		return $query->result();
	}
	
	public function get_child_folders($parent) {
		$cmd = "SELECT [FolderGuid],[FolderName] FROM [RepUIDB].[dbo].[Folders]	WHERE [IsDeleted] = 0 and [ParentGuid] = '".$parent."'	ORDER by [CreatedDateTime]";
		$query = $this->db->query($cmd);
		return $query->result();
	}
	
	public function get_siteList(){
		$cmd = "select [SiteName] from [RepUIDB].[dbo].[Sites]";
		$query = $this->db->query($cmd);
		return $query->result();
	}
		
	public function get_report_29x($datefrom,$dateto,$site,$serviceid,$freq){
		$site = rtrim($site,",");
		$sites = explode(",",$site);
		$sid = "";
		foreach($sites as $sitelist){
			$sid .= "'".$sitelist."',";
		}
		$sid =  rtrim($sid,",");
		$serviceid = rtrim($serviceid,",");
	
	
		$serviceids = explode(",",$serviceid);
		$svd = "";
		foreach($serviceids as $serviceidlist){
			$svd .= "'".$serviceidlist."',";
		}
		$svd =  rtrim($svd,",");
		
	$cmd = "declare @FromDateTime datetime = '".$datefrom."' , @ToDateTime datetime = '".$dateto."', @Frequency varchar(20) = '".$freq."';
			SET NOCOUNT ON;

			IF (@Frequency = 'W')
			BEGIN
				SET @FromDateTime = (SELECT CONVERT(VARCHAR(20), (dateadd(dd, 0 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0, @FromDateTime))) %7 , dateadd(hour, 0,@FromDateTime))), 101))
				SET @ToDateTime = (SELECT CONVERT(VARCHAR(20), (dateadd(dd, 6 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0, @ToDateTime))) %7 , dateadd(hour, 0,@ToDateTime))), 101))
			END;

		with frequency as (Select @Frequency as frequency)
		,intr as (Select @FromDateTime as StartDate,@ToDateTime as StopDate
		,case when frequency = 'Fifteen' then 900	when frequency = 'Thirty' then 1800
		when frequency = 'Sixty' then 3600  when frequency = 'D' then 86400 when frequency = 'W' then 604800 when frequency = 'M'
		then (case when datename(month, @FromDateTime) = 'September' or datename(month, @FromDateTime) = 'April' or datename(month, @FromDateTime) = 'June' or datename(month, @FromDateTime) = 'November' then 86400 * 31
		when datename(month, @FromDateTime) = 'February' then 86400 * 31 else 86400 * 31 end)
		when frequency = 'Y' then 31536000	when frequency = 'Morning' or frequency = 'Afternoon' or frequency = 'Graveyard' then 3600 * 8 end as interval
		,case when frequency = 'Fifteen' then 15 when frequency = 'Thirty' then 15 * 2	when frequency = 'Sixty' then 15 * 4  when frequency = 'D' then 15 * 4 * 24
		when frequency = 'W' then 15 * 4 * 24 * 7 when frequency = 'M'
		then (case when datename(month, @FromDateTime) = 'September' or datename(month, @FromDateTime) = 'April'	 or datename(month, @FromDateTime) = 'June'	 or datename(month, @FromDateTime) = 'November' then 15 * 4 * 24 * 31
				 when datename(month, @FromDateTime) = 'February' then 15 * 4 * 24 * 31	 else 15 * 4 * 24 * 31 end)
				 when frequency = 'Y' then 15 * 4 * 24 * 365 when frequency = 'Morning' or frequency = 'Afternoon' or frequency = 'Graveyard' then 15 * 4 * 8 end as frequency_in_sec,frequency from frequency)
		, recursive_interval as (
			select interval,case when frequency = 'Morning' or frequency = 'Afternoon' or frequency = 'Graveyard' then  DATEADD(SECOND, interval * 0 ,  dateadd(hour,6,StartDate)) 
			when frequency = 'W' then CONVERT(VARCHAR(20), (dateadd(dd, 0 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,StartDate))) %7 , dateadd(hour, 0,StartDate))), 101)
			else DATEADD(SECOND, interval * 0 ,  dateadd(hour,0,StartDate)) end as IntervalStart ,case when frequency = 'Morning' or frequency = 'Afternoon' or frequency = 'Graveyard' 
			then  DATEADD(SECOND, interval * 1, dateadd(hour,6,StartDate)) else DATEADD(SECOND, interval * 1, dateadd(hour,0,StartDate)) end as IntervalEnd
			,StartDate,StopDate from intr union all select interval ,DATEADD(SECOND, interval * 1, IntervalStart) as IntervalStart ,DATEADD(SECOND, interval * 2, IntervalStart) as IntervalEnd
			,StartDate,StopDate	from recursive_interval where DATEADD(SECOND, interval * 1.5,  IntervalStart) < StopDate )
		,handled_agent_site as (
			select distinct acdCallDetail.SeqNum as seqnum, dateadd(hour, 8, dateadd(minute, datediff(minute, 0, dateadd(minute, 0, acdCallDetail.CallStartDt)) / 15 * 15, 0)) as handled_interval,
			acdCallDetail.CallStartDt as callstartdt,acdCallDetail.CallId as callid,acdCallDetail.CallTypeId,acdCallDetail.CallActionId as callactionid,acdCallDetail.User_Id as [user_id],
			acdCallDetail.Station as station,acdCallDetail.Service_Id as service_id,[service].Service_c,acdCallDetail.CallQStartDt,acdCallDetail.QueueStartDt as queuestartdt,
			acdCallDetail.QueueEndDt as queueenddt,acdCallDetail.WrapEndDt as wrapenddt,isnull(sites.SiteName, 'MOC') as SiteName from RepDb..ACDCallDetail acdCallDetail
			left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Service] [service] on acdCallDetail.Service_Id = [service].Service_Id
			left join RepUIDB..Stations stations on acdCallDetail.Station = stations.Station left join RepUIDB..Sites sites on stations.SiteGuid = sites.SiteGuid -- dnis.SiteGuid = sites.SiteGuid
			where dateadd(hour, 8, acdCallDetail.CallStartDt) >= dateadd(d, 0, @FromDateTime) and  dateadd(hour, 8, acdCallDetail.CallStartDt) <= dateadd(d, 0, @ToDateTime)
			and CallActionId in (8,3, 5, 6 ) and acdCallDetail.QueueEndDt is not null)
		, base_table_vhis28 as (
		select acdCallDetail.SeqNum as seqnum,dateadd(hour, 8, dateadd(minute, datediff(minute, 0, dateadd(minute, 0, acdCallDetail.CallStartDt)) / 15 * 15, 0)) as FifteenMinuteInterval,
		dateadd(hour,8,acdCallDetail.CallStartDt) as callstartdt,acdCallDetail.CallId as callid,datediff(second, dateadd(hour,8,ACDCallDetail.QueueStartDt), dateadd(hour,8,ACDCallDetail.QueueEndDt)) as AnswerDelay
		,case when datediff(second, ACDCallDetail.QueueStartDt, ACDCallDetail.QueueEndDt) > 20 THEN 1 else 0 end as SkillsetAnsAfterThreshold,acdCallDetail.CallTypeId as calltypeid,
		acdCallDetail.CallActionId as callactionid,acdCallDetail.User_Id as [user_id],acdCallDetail.Station as station,acdCallDetail.Service_Id as service_id,
		(select Service_c from [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Service] where Service_Id = acdCallDetail.Service_Id) as servicename,
		acdCallDetail.CallQStartDt as callqstartdt,acdCallDetail.QueueStartDt as queuestartdt,acdCallDetail.QueueEndDt as queueenddt,acdCallDetail.WrapEndDt as wrapenddt,acdCallDetail.sitename as SiteName
		,isnull(skills.Skill_Id, 
				case acdCallDetail.Service_Id
					when 4000013 then 4000019 when 4000018 then 4000019 when 4000019 then 4000023 when 4000020 then 4000023 when 4000027 then 4000050 when 4000028 then 4000050
					when 4000029 then 4000051 when 4000030 then 4000051 when 4000021 then 4000044 when 4000022 then 4000044 when 4000023 then 4000049 when 4000024 then 4000049
					when 4000025 then 4000048 when 4000026 then 4000048
				end	)as skill_id
		,isnull(skills.Skill_Desc, case acdCallDetail.Service_Id
					when 4000013 then 'Res_Customer_Assist_sk'	when 4000018 then 'Res_Customer_Assist_sk'	when 4000019 then 'Biz_Customer_Assist_sk'	when 4000020 then 'Biz_Customer_Assist_sk'
					when 4000027 then 'Cxe_Customer_Assist_sk'	when 4000028 then 'Cxe_Customer_Assist_sk'	when 4000029 then 'Dpa_Customer_Assist_sk'	when 4000030 then 'Dpa_Customer_Assist_sk'
					when 4000021 then 'Pres_Customer_Assist_sk'	when 4000022 then 'Pres_Customer_Assist_sk'	when 4000025 then 'Gov_Customer_Assist_sk'	when 4000026 then 'Gov_Customer_Assist_sk'
					when 4000024 then 'Kwatch_Customer_Assist_sk'	when 4000023 then 'Kwatch_Customer_Assist_sk'
				end	) as Skill_Desc	,isnull(asbr.AgentSkillLevel, 10) as AgentSkillLevel,service_c
		from handled_agent_site acdCallDetail left join REPDB.[dbo].[ASBRCallSkillDetail] asbr on acdCallDetail.[SeqNum] = asbr.[SeqNum] AND asbr.[Skill_Id] not in (4000001, 4000002)
		left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].Skills skills on asbr.Skill_Id = skills.Skill_Id)

		, base_agent_Count as (select distinct dateadd(hour,8,a.callstartdt) as CallStartDt,dateadd(hour,8,b.logindt) as LoginDt,dateadd(hour,8,b.logoutdt) as LogoutDt,b.User_Id as Online
		,a.Service_Id,aa.Station,aa.ModifiedDt,st.SiteName,a.CallActionId,m.Param15,b.User_Id,asbr.AgentSkillLevel from repdb.dbo.ACDCallDetail a left join repdb.dbo.AgentLoginLogout b
		on a.Service_Id = b.Service_Id and dateadd(hour,8,a.callstartdt) between dateadd(hour,8,b.logindt)  and dateadd(hour,8,b.LogoutDt) left join repdb.dbo.MediaDataDetail m
		on b.Service_Id = m.Service_Id and dateadd(hour,8,a.callstartdt) = dateadd(hour,8,m.callstartdt) and m.SeqNum = a.SeqNum and m.CallId = a.CallId left join repdb.dbo.AgentStateAudit aa
		on b.User_Id = aa.User_Id and DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,b.LoginDt)) / 1 * 1, 0)) = DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,aa.ModifiedDt)) / 1 * 1, 0)) 
		left join RepUIDB.dbo.Stations s on aa.Station = s.Station left join RepUIDB.dbo.Sites st on s.SiteGuid = st.SiteGuid and aa.Agent_Index is not null left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Service] service
		on service.Service_Id = a.Service_Id left join repdb.dbo.ASBRCallSkillDetail asbr on asbr.SeqNum = a.SeqNum and asbr.CallId = a.CallId and asbr.CallStartDt = a.CallStartDt where a.Service_Id IN (".$svd.")
		and a.CallActionId in (5, 6, 18) and asbr.AgentSkillLevel = 0)

		, table_2 as (
		select distinct dateadd(hour,8,a.callstartdt) as CallStartDt,dateadd(hour,8,b.logindt) as LoginDt,dateadd(hour,8,b.logoutdt) as LogoutDt,b.User_Id as Online,a.Service_Id,a.CallActionId,m.Param15,st.SiteName as DNIS_SiteTagging
		from repdb.dbo.ACDCallDetail a left join repdb.dbo.AgentLoginLogout b on a.Service_Id = b.Service_Id and dateadd(hour,8,a.callstartdt) between dateadd(hour,8,b.logindt)  and dateadd(hour,8,b.LogoutDt)
		left join repdb.dbo.MediaDataDetail m on m.CallStartDt = a.CallStartDt left join RepUIDB.dbo.DNIS D on d.DNIS = m.Param15 left join RepUIDB.dbo.Sites st on d.SiteGuid = st.SiteGuid
		left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Service] service on service.Service_Id = a.Service_Id where a.Service_Id IN (".$svd.") and a.CallActionId in (5, 6, 18))

		, online_agent_perStation as (
		select distinct DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, callstartDt)) / 15 * 15, 0) as FifteenMinuteInterval,CallStartDt,count(distinct Online) as Count,SiteName as StationOnline,Service_Id,CallActionId
		,Param15,AgentSkillLevel from base_agent_Count
		group by DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, callstartDt)) / 15 * 15, 0),SiteName,Service_Id,CallActionId,Param15,CallStartDt,AgentSkillLevel)

		, DNIS_tagging as (
		select distinct DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, callstartDt)) / 15 * 15, 0) as FifteenMinuteInterval,DNIS_SiteTagging as DNIS_Site,Service_Id,CallActionId,Param15 From table_2)
		
		, final_query_abandoned as (
		select distinct	a.FifteenMinuteInterval,a.Count as AgentLoginCount,a.Service_Id,a.CallActionId,a.StationOnline,a.callstartdt,b.DNIS_Site
			  ,case when a.Count = 0 then 'MOC' when a.StationOnline != b.DNIS_Site then 'MOC' else b.DNIS_Site end as SiteName ,a.AgentSkillLevel
		from online_agent_perStation a left join DNIS_tagging b	on a.Service_Id = b.Service_Id	and a.FifteenMinuteInterval = b.FifteenMinuteInterval and a.Param15 = b.Param15)

		, base_online_agents_perInterval as (
		select	FifteenMinuteInterval,count(distinct user_id) as Online,AgentSkillLevel,Service_Id,Skill_Id,SiteName from base_table_vhis28
		group by AgentSkillLevel,FifteenMinuteInterval,Service_Id,Skill_Id,SiteName	)

		, novice_online as (select	FifteenMinuteInterval,Online as Online_Novice,AgentSkillLevel,Service_Id,Skill_Id from	base_online_agents_perInterval	where AgentSkillLevel = 2)
		, devExpert_online as (select FifteenMinuteInterval,Online as Online_DevExpert,AgentSkillLevel,Service_Id,Skill_Id from base_online_agents_perInterval where AgentSkillLevel = 6)
		, expert_online as (select	FifteenMinuteInterval,Online as Online_Expert,AgentSkillLevel,Service_Id,Skill_Id from	base_online_agents_perInterval where AgentSkillLevel = 10)
		, calls_offered_table as (
			select seqnum as Offered,FifteenMinuteInterval,CallStartDt,Service_Id,service_c,AgentSkillLevel,Skill_Id,Skill_Desc,CallActionId,siteName,user_id from base_table_vhis28 where callactionid in (3,5,6,8) and queueenddt is not null
			and (servicename like '%SP1' or servicename like '%SP2'))
		, expert_offered as (select	Offered,FifteenMinuteInterval,CallStartDt,Service_Id,service_c,AgentSkillLevel,Skill_Id,Skill_Desc,CallActionId,siteName,user_id from calls_offered_table where AgentSkillLevel IN (0,10))
		
		, calls_answered_table_Group as (
			select	FifteenMinuteInterval,CallStartDt,seqnum as Answered,datediff(second, queueStartDt, queueEndDt) as AnswerDelay,datediff(second, QueueEndDt, WrapEndDt) as HandlingTime,user_id,QueueStartDt,QueueEndDt
			,WrapEndDt,Service_Id,AgentSkillLevel,Skill_Id,CallActionId,service_c,Skill_Desc,SiteName from base_table_vhis28 where AgentSkillLevel != 0 and queuestartdt is not null
			group by FifteenMinuteInterval,CallStartDt,user_id,QueueStartDt,QueueEndDt,WrapEndDt,Service_Id,AgentSkillLevel,Skill_Id,CallActionId,service_c,Skill_Desc,SiteName,seqnum)
		
		, calls_offered_group as (
		select	FifteenMinuteInterval,sum(Offered) as Offered,AgentSkillLevel,Service_Id,Service_c,Skill_Id,Skill_Desc,siteName,CallStartDt,user_id from calls_offered_table
		group by FifteenMinuteInterval,AgentSkillLevel,Service_Id,Service_c,Skill_Id,Skill_Desc,siteName,user_id,CallStartDt)

		, answered_group as (
			select	FifteenMinuteInterval,Answered,sum(AnswerDelay) as AnswerDelay_Group,sum(HandlingTime) as HandlingTime_Group,sum(case when AnswerDelay <= 20 then 1 else 0 end) as WithinThreshold
			,AgentSkillLevel,user_id,Service_Id,Service_c,Skill_Id,Skill_Desc,SiteName,CallStartDt,callactionid from calls_answered_table_Group	
			group by FifteenMinuteInterval,AgentSkillLevel,Service_Id,Service_c,SiteName,Skill_Id,Skill_Desc,user_id,Answered,CallStartDt,callactionid)

		, answered_Group_With_Avg as (
			select	FifteenMinuteInterval,Answered,AnswerDelay_Group as ASA_Group,HandlingTime_Group as AHT_Group,WithinThreshold,AgentSkillLevel,Service_Id,Service_c,Skill_Id,Skill_Desc,SiteName,User_Id,CallStartDt	from answered_group
			where callactionid In (3,8))

		, base_NotReady as (
		select distinct	dateadd(hour,8,NotReadyStartDt) as NotReadyStartDt,dateadd(hour,8,NotReadyEndDt) as NotReadyEndDt,dateadd(hour,8,LoginDt) as LoginDt,dateadd(hour,8,LogoutDt) as LogoutDt,b.User_Id as NotReady_UserOnline
		,a.User_Id as AnsweredUser,agl.User_Id as User_Login,a.CallActionId,dateadd(hour,8,a.CallStartDt) as CallStartDt,isnull(coalesce(st.SiteName, (select top 1 sitename from repdb.dbo.ACDCallDetail acd
		left join repdb.dbo.AgentLoginLogout agl1 on acd.User_Id = agl1.User_Id	and acd.Service_Id = agl1.Service_Id and acd.CallStartDt between agl1.LoginDt and agl1.LogoutDt
		left join repuidb.dbo.stations s1 on s1.Station = acd.Station left join repuidb.dbo.sites b1 on s1.siteguid = b1.siteguid where acd.station = s1.station and acd.CallStartDt = a.CallStartDt) ), 'MOC') as SiteName
		,dateadd(hour,8,queueenddt) as QueueEndDt from repdb.dbo.ACDCallDetail a left join repdb.dbo.AgentLoginLogout agl on a.QueueEndDt between agl.LoginDt and agl.LogoutDt and a.Service_Id = agl.Service_Id
		left join repdb.dbo.AgentNotReadyDetail b on a.QueueEndDt between NotReadyStartDt and NotReadyEndDt	and NotReadyStartDt between agl.LoginDt and LogoutDt and NotReadyEndDt between agl.LoginDt and LogoutDt
		and b.User_Id = agl.User_Id	left join repdb.dbo.AgentStateAudit asa	on asa.User_Id = b.User_Id and 	DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,agl.LoginDt)) / 1 * 1, 0)) 
		= DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,asa.ModifiedDt)) / 1 * 1, 0)) left join RepUIDB.dbo.Stations s	on asa.Station = s.Station left join RepUIDB.dbo.Sites st
		on st.SiteGuid = s.SiteGuid	left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Service] service on service.Service_Id = a.Service_Id where a.CallActionId in (8, 3, 5, 6) and a.QueueStartDt is not null)


		,base_Ready as (
			select	dateadd(hour,8,IdleStartDt) as IdleStartDt,dateadd(hour,8,IdleEndDt) as IdleEndDt,b.User_Id as Online,a.User_Id as AnsweredUser,a.CallActionId,dateadd(hour,8,a.CallStartDt) as CallStartDt,isnull(coalesce(st.SiteName, 
			(select top 1 sitename from repdb.dbo.ACDCallDetail acd	left join repdb.dbo.AgentLoginLogout agl1 on acd.User_Id = agl1.User_Id	and acd.Service_Id = agl1.Service_Id and acd.CallStartDt between agl1.LoginDt and agl1.LogoutDt
			left join repuidb.dbo.stations s1 on s1.Station = acd.Station left join repuidb.dbo.sites b1 on s1.siteguid = b1.siteguid where acd.station = s1.station and acd.CallStartDt = a.CallStartDt) ), 'MOC') as SiteName
			from repdb.dbo.ACDCallDetail a left join repdb.dbo.AgentIdleDetail b on a.QueueEndDt between IdleStartDt and IdleEndDt left join repdb.dbo.AgentLoginLogout agl on a.QueueEndDt between agl.LoginDt and agl.LogoutDt
			and a.Service_Id = agl.Service_Id left join repdb.dbo.AgentStateAudit asa on asa.User_Id = b.User_Id and DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,agl.LoginDt)) / 1 * 1, 0)) 
			= DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,asa.ModifiedDt)) / 1 * 1, 0)) left join RepUIDB.dbo.Stations s	on asa.Station = s.Station left join RepUIDB.dbo.Sites st
			on st.SiteGuid = s.SiteGuid	left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Service] service on service.Service_Id = a.Service_Id where a.CallActionId in (8, 3, 5, 6) and a.QueueStartDt is not null)

		, base_outbound as (
			select distinct	dateadd(hour, 8 ,a.callstartdt) as CallStartDt,dateadd(hour,8,b.logindt) as LoginDt,dateadd(hour,8,b.logoutdt) as LogoutDt,b.User_Id as Online,b.Service_Id,aa.Station,aa.ModifiedDt
			,st.SiteName as SiteName2,coalesce(st.SiteName, (select top 1 sitename from repdb.dbo.ACDCallDetail acd	left join repdb.dbo.AgentLoginLogout agl1 on acd.User_Id = agl1.User_Id	and acd.Service_Id = agl1.Service_Id
			and acd.CallStartDt between agl1.LoginDt and agl1.LogoutDt left join repuidb.dbo.stations s1 on s1.Station = acd.Station left join repuidb.dbo.sites b1 on s1.siteguid = b1.siteguid
			where acd.station = s1.station and agl1.logindt = b.logindt)) as SiteName,a.CallActionId from repdb.dbo.AgentLoginLogout b left join repdb.dbo.ACDCallDetail a 
			on a.Service_Id = b.Service_Id and dateadd(hour,8,a.QueueEndDt) between dateadd(hour,8,b.logindt)  and dateadd(hour,8,b.LogoutDt)
			left join repdb.dbo.AgentStateAudit aa on b.User_Id = aa.User_Id and DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,b.LoginDt)) / 1 * 1, 0)) 
			= DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,aa.ModifiedDt)) / 1 * 1, 0)) and aa.Agent_Index is not null left join RepUIDB.dbo.Stations s
			on aa.Station = s.Station left join RepUIDB.dbo.Sites st on s.SiteGuid = st.SiteGuid where dateadd(hour,8,LoginDt) between @FromDateTime and @ToDateTime and a.CallActionId in (8, 3, 5, 6))

		, computation_notReady_1 as (select distinct CallStartDt,NotReady_UserOnline as NotReady_Count,isnull(SiteName, 'MOC') as SiteName,CallActionId,User_Login,AnsweredUser,NotReadyStartDt from base_NotReady)
		, computation_notReady as (select CallStartDt,sum(1) as NotReady_Count,SiteName,CallActionId from computation_notReady_1 group by CallStartDt,SiteName,CallActionId)
		, computation_Ready_1 as (select CallStartDt,online as Ready_Count,isnull(SiteName, 'MOC') as SiteName,CallActionId,AnsweredUser as User_Id,Online,IdleStartDt,IdleEndDt from base_Ready)
		, computation_Ready as (select	CallStartDt,SiteName,CallActionId ,sum(1) as Ready,Ready_Count from	computation_Ready_1	group by CallStartDt,SiteName,CallActionId,Ready_Count)
		, computation_Outbound as (select	logindt,logoutdt,isnull(sitename, 'MOC') as SiteName,count(distinct Online) as Outbound_Count,a.Service_Id as Service_Id
		from base_outbound a left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Service] service on service.service_id = a.Service_Id	where service.servicetype_id = 4 group by logindt,logoutdt,sitename,a.Service_Id	)

		, Ready_Answered_Combined as (select distinct b.CallStartDt,a.service_id,b.Online as Online,a.AHT_Group,a.ASA_Group,a.Answered,a.WithinThreshold,a.user_id,b.SiteName	from computation_Ready_1 b
		left join answered_Group_With_Avg a	on b.Online = a.user_id	and b.CallStartDt = a.callstartdt and b.SiteName = a.SiteName)

		, computed as (select a.CallStartDt,b.SiteName,a.SiteName as Site,b.service_id,b.Online,a.NotReady_Count,b.Answered,b.ASA_Group,b.AHT_Group,b.WithinThreshold,b.user_id
		from computation_notReady_1 a left join	Ready_Answered_Combined b on a.CallStartDt  = b.CallStartDt	and a.User_Login = b.user_id and a.SiteName = b.SiteName)

		, Offered_NotDistinct as (
			select distinct	a.callstartdt,dateadd(hour, 0, dateadd(minute, datediff(minute, 0, dateadd(minute, 0, a.callstartdt)) / 15 * 15, 0)) as Intervals,a.SiteName,a.service_id
			,a.Offered,b.Answered,b.WithinThreshold,b.ASA_Group,b.AHT_Group,b.Online as Count_Ready,b.NotReady_Count,d.Outbound_Count as Count_Outbound,a.service_c
			from calls_offered_group a left join computed b	on a.callstartdt  = b.CallStartDt left join computation_Outbound d	on a.callstartdt between d.LoginDt and d.LogoutDt and d.SiteName = a.SiteName)

		, filtered_finished_query as (select * from intr,Offered_NotDistinct where callstartdt between StartDate and StopDate)

		, base_computation_Finished_query as (select cast(StartDate as datetime) as  BeginInterval,cast(StopDate as datetime) as  EndInterval ,* from filtered_finished_query)
		, recursive_computation_AgentNotReady as (
		select case when frequency = 'Morning' or frequency = 'Afternoon' or frequency = 'Graveyard' 
		then  DATEADD(SECOND, interval * 0 ,  dateadd(hour,6,BeginInterval)) 
		else DATEADD(SECOND, interval * 0 ,  dateadd(hour,0,BeginInterval)) end as IntervalStart 
		,case when frequency = 'Morning' or frequency = 'Afternoon' or frequency = 'Graveyard' 
		then  DATEADD(SECOND, interval * 1, dateadd(hour,6,BeginInterval)) 
		else DATEADD(SECOND, interval * 1, dateadd(hour,0,BeginInterval)) end as IntervalEnd
		,Intervals,interval,StartDate,EndInterval,SiteName,Offered,Answered,WithinThreshold,ASA_Group,AHT_Group,Count_Ready,NotReady_Count,Count_Outbound,service_id,callstartdt,frequency,service_c from base_computation_Finished_query 
		group by BeginInterval,EndInterval,Intervals,interval,StartDate,EndInterval,SiteName,Offered,Answered,WithinThreshold,ASA_Group,AHT_Group,Count_Ready,NotReady_Count,Count_Outbound,service_id,callstartdt,frequency,service_c
		union all select DATEADD(SECOND, interval * 1, IntervalStart) as IntervalStart,DATEADD(SECOND, interval * 2, IntervalStart) as IntervalEnd,Intervals,interval,StartDate,EndInterval,SiteName,Offered,Answered,WithinThreshold
		,ASA_Group,AHT_Group,Count_Ready,NotReady_Count,Count_Outbound,service_id,callstartdt,frequency,service_c from recursive_computation_AgentNotReady where DATEADD(SECOND, interval * 1.5,  IntervalStart) < EndInterval )

		, raw_finished_query as (select distinct IntervalStart,IntervalEnd,StartDate,EndInterval,SiteName,Offered,Answered,WithinThreshold,ASA_Group,AHT_Group,Count_Ready,NotReady_Count,Count_Outbound,frequency,service_c,callstartdt,case when callstartdt >= IntervalStart and callstartdt <= IntervalEnd
		then service_id	else 0 	end as service_id from recursive_computation_AgentNotReady where callstartdt >= @FromDateTime and callstartdt <= @ToDateTime)
		, logged_in_time_within_interval as (select * from raw_finished_query where service_id > 0)
		, total as (select	IntervalStart,IntervalEnd,SiteName,count(distinct Offered) as Offered,count(distinct Answered) as Answered,sum( WithinThreshold) as WithinThreshold,sum( ASA_Group) as ASA_Group,sum( AHT_Group)  as AHT_Group
		,count(distinct Count_Ready) as Total_Online,count(distinct NotReady_Count) as Total_Offline,Total_Onlinex = STUFF
		  (( SELECT ' , ' + Count_Ready	FROM logged_in_time_within_interval gg where gg.IntervalStart=logged_in_time_within_interval.IntervalStart and
					 gg.IntervalEnd=logged_in_time_within_interval.IntervalEnd and gg.SiteName=logged_in_time_within_interval.SiteName and gg.service_id=logged_in_time_within_interval.service_id FOR XML PATH('')), 1, 2, N'')
	 
		,Total_Offlinex = STUFF
		((SELECT ' , ' + NotReady_Count FROM logged_in_time_within_interval gg where gg.IntervalStart=logged_in_time_within_interval.IntervalStart and
				 gg.IntervalEnd=logged_in_time_within_interval.IntervalEnd and gg.SiteName=logged_in_time_within_interval.SiteName and  gg.service_id=logged_in_time_within_interval.service_id FOR XML PATH('')), 1, 2, N'')
		,Count_Outbound,frequency,service_id,service_c from logged_in_time_within_interval where callstartdt >= @FromDateTime and callstartdt <= @ToDateTime and callstartdt between IntervalStart and IntervalEnd
		group by IntervalStart,IntervalEnd,SiteName,Count_Outbound,frequency,service_id,service_c)

		, final_query as (
		select *,IntervalStart as Intervals,DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 30,IntervalStart)) / 30 * 30, 0)) as '30MinsInterval'
		,DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 60,IntervalStart)) / 60 * 60, 0)) as '60MinsInterval'
		,CONVERT(VARCHAR(20), (dateadd(dd, 0 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,IntervalStart))) %7 , dateadd(hour, 0,IntervalStart))), 101) as Sunday
		,CONVERT(VARCHAR(20), (dateadd(dd, 6 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,IntervalStart))) %7 , dateadd(hour, 0,IntervalStart))), 101) as Saturday
		,convert(varchar(10), dateadd(hour, 0,IntervalStart), 101) + ' ' + format(CAST(DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,IntervalStart)) / 15 * 15, 0)) as Datetime), 'hh:mm:ss.fff') as Minus15Mins
		,convert(varchar(10), dateadd(hour, 0,IntervalStart), 101) + ' ' + format(CAST(DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,IntervalStart)) / 30 * 30, 0)) as Datetime), 'hh:mm:ss.fff') as Minus30Mins
		,convert(varchar(10), dateadd(hour, 0,IntervalStart), 101) + ' ' + format(CAST(DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,IntervalStart)) / 60 * 60, 0)) as Datetime), 'hh:mm:ss.fff') as Minus60Mins
		,datepart(wk, dateadd(hour, 0,IntervalStart)) as Week_count,datepart(w, dateadd(hour, 0,IntervalStart)) as Day_Count,datename(dw, dateadd(hour, 0,IntervalStart)) as Day_Name
		,convert(varchar(10), dateadd(hour, 0,IntervalStart), 101) as perDate,DATEPART(M, DATEADD(hour, 0,IntervalStart)) AS Month_Count
		,cast(datename(m, dateadd(hour,0,IntervalStart)) as varchar(10)) + ', ' + cast(year(dateadd(hour,0,IntervalStart)) as varchar(10)) AS MONTH_NAME
		,cast(year(dateadd(hour,0,IntervalStart)) as varchar(10)) + ', ' + cast(datename(m, dateadd(hour,0,IntervalStart)) as varchar(10)) as Year_Month
		,YEAR(DATEADD(hour, 0,IntervalStart)) AS YEAR,DATEADD(hour, 0,IntervalStart) AS DATE_TIME,convert(varchar(10), dateadd(hour, 0,IntervalStart), 101) +' '+
			case when datepart(hour, dateadd(hour, 0, IntervalStart)) <= 5 or datepart(hour, dateadd(hour, 0, IntervalStart)) >= 22 then '10PM - 6AM'
			when datepart(hour, dateadd(hour, 0, IntervalStart)) > = 14 and datepart(hour, dateadd(hour, 0, IntervalStart)) <= 21 then '2PM - 10PM'
			when datepart(hour, dateadd(hour, 0, IntervalStart)) >= 6 and datepart(hour, dateadd(hour, 0, IntervalStart)) <= 13 then '6AM - 2PM' end as Service_Shift from total)

		, final_withInterval as (
		select 	CAST((CASE
				WHEN @Frequency = 'Fifteen'	THEN convert(varchar(15), Intervals, 101) +' '+ convert(varchar(15), cast(Intervals as time), 108) + ' - ' + convert(varchar(15), cast(IntervalEnd as time), 108)
				WHEN @Frequency = 'Thirty'	THEN convert(varchar(15), Intervals, 101) +' '+ convert(varchar(15), cast(Intervals as time), 108) + ' - ' + convert(varchar(15), cast(IntervalEnd as time), 108)
				WHEN @Frequency = 'Sixty'	THEN convert(varchar(15), Intervals, 101) +' '+ convert(varchar(15), cast(Intervals as time), 108) + ' - ' + convert(varchar(15), cast(IntervalEnd as time), 108)
				WHEN @Frequency = 'D'		THEN CAST([perDate] as varchar(max))
				WHEN @Frequency = 'W'		THEN CAST(convert(varchar(10), Sunday, 101)  +' - '+  convert(varchar(10), Saturday, 101) as varchar(max))
				WHEN @Frequency = 'M'		THEN CAST([MONTH_NAME] as varchar(max))
				WHEN @Frequency = 'Y'		THEN CAST([YEAR] as varchar(max))
				when @Frequency = 
		(case when service_shift like '%6AM - 2PM' then 'Morning'
			  when Service_Shift like '%2PM - 10PM' then 'Afternoon'
			  when Service_Shift like '%10PM - 6AM' then 'Graveyard' end)
			  then CAST([Service_Shift] as varchar(max)) END) as varchar(max)) as Interval
			  ,SiteName,Offered,Answered,WithinThreshold,ASA_Group,AHT_Group,Total_Online,Total_Offline,Total_Onlinex,Total_Offlinex,isnull(Count_Outbound, 0) as Count_Outbound
			  ,service_id,frequency,IntervalStart,IntervalEnd,service_c from final_query)

		select distinct * from final_withInterval where IntervalStart between @FromDateTime and @ToDateTime and Interval is not null and service_id in (".$svd.") and sitename in (".$sid.") order by IntervalStart asc
		option(maxrecursion 0)";

		$query = $this->db->query($cmd);	   
		return $query;
	}
}
?>