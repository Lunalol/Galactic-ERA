{OVERALL_GAME_HEADER}

<div id='ERAplayArea'>
	<div id='ERAfleets' class='ERAhide'></div>
	<div id='ERAboard'></div>
</div>
<div id='ERApanels'>
	<div id='ERA-DP'></div>
</div>
<div id='ERAcontrols'>
	<span id='ERAzoomMinus'>🔍</span>
	<input id='ERAzoomLevel' type='range' style='vertical-align:middle;'/>
	<span id='ERAzoomPlus'>🔎</span>
	<span id='ERAhome' class="fa fa-home fa-lg"></span>
	<span id='ERArotateAntiClockwise'>⭯</span>
	<input id='ERArotate' type='range' min='-180' max='180' value='0' style='vertical-align:middle;'/>
	<span id='ERArotateClockwise'>⭮</span>
</div>

<script type="text/javascript">
	var ERAchoice = "<div class='ERAchoice' id='ERAchoice'></div>";
	var ERAstarPeople = "<div class='ERAstarPeople' tabindex='-1' starpeople='${starpeople}'><img draggable='false'/></div>";
	var ERAdominationCards = "<div class='ERAdominationCards' id='ERAdominationCards'></div>";
	var ERAdominationCard = "<div class='ERAdominationCard' id='ERAdominationCard-${domination}' tabindex='-1' domination='${domination}'><img draggable='false'/></div>";
	var ERAsector = "<div class='ERAsector ERAsector-${id}' id='ERAsector-${id}' sector=${sector} style='left:${x}px;top:${y}px;transform:rotate(${angle}deg)'></div>";
	var ERAcounter = "<div class='ERAcounter ERAcounter-${color} ERAcounter-${type}' id='ERAcounter-${id}' counter=${id} location='${location}'></div>";
	var ERAhomeStar = "<div class='ERAhomeStar ERAhomeStar-${color}' id='ERAhomeStar-${id}' homeStar=${id} color='${color}' location='${location}'></div>";
	var ERAship = "<div class='ERAship ERAship-${color}' id='ERAship-${id}' ship=${id} color='${color}' location='${location}'></div>";
	var ERAfleet = "<div class='ERAfleet' id='ERAfleet-${fleet}' fleet='${fleet}' ships='${ships}' location='${location}' style='display:flex;flex-direction:column;align-items:center;'></div>";
	var ERApanel = "\
<div class='ERApanel ERApanel-${color}'>\n\
	<div style='display:flex;flex-direction:row;justify-content:bottom;'>\n\
		<div style='display:flex;flex-direction:column;justify-content:center;'>\n\
			<div class='ERAdominationCards' id='ERAdominationCards-${color}'></div>\n\
			<div class='ERAplayerAid' id='ERAplayerAid-${color}' tabindex='-1' playerAid='0'></div>\n\
		</div>\n\
		<div style='display:flex;flex-direction:column;justify-content:center;'>\n\
			<div style='display:flex;flex-direction:row;justify-content:center;'>\n\
				<div class='ERAemergencyReserve ERAemergencyReserve-${color}' id='ERAemergencyReserve-${color}' tabindex='-1'></div>\n\
				<div class='ERApopulationTrack' id='ERApopulationTrack-${color}'></div>\n\
			</div>\n\
			<div class='ERAtechTrack' id='ERAtechTrack-${color}'></div>\n\
		</div>\n\
		<div style='display:flex;flex-direction:column;justify-content:end;width:400px'>\n\
			<div class='ERAstarPeople' tabindex='-1'><img draggable='false'/></div>\n\
		</div>\n\
	</div>\n\
</div>";
	var ERAfaction = "<div class='ERAfaction' id='ERAfaction-${color}'></div></div>";
	var ERAtechnologies = "<div class='ERAtechnologies' id='ERAtechnologies-${color}'></div></div>";
</script>

{OVERALL_GAME_FOOTER}
