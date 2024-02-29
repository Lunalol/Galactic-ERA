/**
 *
 * @author Lunalol
 */
define(["dojo", "dojo/_base/declare", "dijit", "ebg/core/gamegui", "ebg/counter",
	g_gamethemeurl + "modules/JavaScript/constants.js",
	g_gamethemeurl + "modules/JavaScript/board.js",
	g_gamethemeurl + "modules/JavaScript/factions.js",
	g_gamethemeurl + "modules/JavaScript/counters.js",
	g_gamethemeurl + "modules/JavaScript/ships.js"
], function (dojo, declare, dijit, gamegui)
{
	return declare("bgagame.galacticera", gamegui,
	{
		constructor: function ()
		{
			console.log('galacticera constructor');
//
			this.default_viewport = 'initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,width=device-width,user-scalable=no';
//
			this.dontPreloadImage('background.jpg');
			this.ensureSpecificGameImageLoading(['playerAids/0.jpg', 'playerAids/1.jpg', 'playerAids/2.jpg', 'playerAids/3.jpg']);
		},
		setup: function (gamedatas)
		{
			console.log("Starting game setup");
			console.debug(gamedatas);
//
			dojo.destroy('debug_output');
//
			dojo.place(`<div id='ERAalien' class='upperrightmenu_item' style='margin-top:10px;font-size:x-large;'>👽</div>`, 'upperrightmenu', 'first');
			dojo.connect($('ERAalien'), 'click', () => {
				if (!this.isSpectator) this.action('GODMODE', {god: JSON.stringify({action: 'toggle'})})
			});
//
// Translations
//
			this.GALATIC_STORIES = {
				Journeys: {
					1: [
						_('Every player with the STO alignment at the end of a round scores 1 DP'),
						_('All players score 1 DP for every player they are “in contact” with at the end of the round')
					],
					2: [
						_('Every player with the STS alignment at the end of a round scores 1 DP'),
						_('Every player “at war” with at least one other player at the end of the round scores 1 DP'),
						_('All players score 2 DP for every star outside of their home star sector that they take from another player'),
						_('All players score 2 DP for every battle they win outside of their home star sector<BR>Battles where all opposing ships retreated before combat are not counted')
					],
					3: [
						_('Every player with the STO alignment at the end of a round scores 1 DP'),
						_('At the end of the round, each player who researched Spirituality in that round and has the highest level (ties allowed) in that field among all the players who also researched that, scores 7 minus their Spirituality level'),
						_('At the end of the round, each player who researched Propulsion in that round and has the highest level (ties allowed) in that field among all the players who also researched that, scores 7 minus their Propulsion level')
					]
				},
				Migrations: {
					1: [
						_('Every player with the STS alignment at the end of a round scores 1 DP'),
						_('All players score 3 DP for every Grow Population action they do in this era<BR>Only Grow Population actions that generated at least one additional population are counted')
					],
					2: [
						_('Every player with the STS alignment at the end of a round scores 1 DP'),
						_('Every player “at war” with at least one other player at the end of the round scores 1 DP'),
						_('All players score 1 DP for every population of another player they remove from a star'),
						_('All players score 1 DP for every battle they win<BR>Battles where all opposing ships retreated before combat are not counted')
					],
					3: [
						_('Every player with the STO alignment at the end of a round scores 1 DP'),
						_('Every player who is the only player to research a certain technology field in a round in this era scores 4 DP (per such field)')
					]
				},
				Rivalry: {
					1: [
						_('Every player with the STO alignment at the end of a round scores 1 DP'),
						_('All players score 1 DP for every Gain Star action they do in this era')
					],
					2: [
						_('Every player with the STS alignment at the end of a round scores 1 DP'),
						_('Every player “at war” with at least one other player at the end of the round scores 1 DP'),
						_('All players score 1 DP for every star of another player they are blocking at the end of the round'),
						_('Every time players “retreat before combat” they lose 2 DP')
					],
					3: [
						_('Every player with the STO alignment at the end of a round scores 1 DP'),
						_('For every technology field, the player who has the highest level in that field at the end of the round scores 3 DP')
					]
				},
				War: {
					1: [
						_('Every player with the STO alignment at the end of a round scores 1 DP'),
						_('All players score 2 DP for every Build Ships action they do in this era')
					],
					2: [
						_('Every player with the STS alignment at the end of a round scores 1 DP'),
						_('Every player “at war” with at least one other player at the end of the round scores 2 DP'),
						_('All players score 1 DP for every star they take from another player'),
						_('All players score 1 DP for every ship of opponents they destroy')
					],
					3: [
						_('Every player with the STO alignment at the end of a round scores 1 DP'),
						_('At the end of the round, each player who researched Military in that round and has the highest level (ties allowed) in that field among all the players who also researched that, scores 7 minus their Military level'),
						_('At the end of the round, each player who researched Robotics in that round and has the highest level (ties allowed) in that field among all the players who also researched that, scores 7 minus their Robotics level')
					]
				}
			};
//
			this.GALACTIC_GOALS = {
			};
//
			this.DOMINATIONS = {
				0: {title: _('Acquisition'), DP: 10,
					A: [_('Conquer/liberate 2 player owned stars on the same turn'),
						_('Play this card when this happens')],
					B: [_('1 DP per neutral star where only you have a ship'),
						_('1 DP per Military level')]},
				1: {title: _('Alignment'), DP: 9,
					A: [_('Can only be played at the end of the scoring phase'),
						_('Have 5 DP and either have more DP (solo variant: tech. levels) than every other player with your alignment or be the only one of your alignment then')
					],
					B: [_('4 DP if you did not get any DP for your alignment in the scoring phase of this round'),
						_('1 DP per Spirituality level')]},
				2: {title: _('Central'), DP: 12,
					A: [_('Own 4 stars in the center sector')],
					B: [_('1 DP per population of one of your stars in the center sector')]},
				3: {title: _('Defensive'), DP: 9,
					A: [_('Own all the stars (except neutron stars) in your home star sector')],
					B: [_('4 DP if no other player owns a star in your home sector + 1 DP per 2 Military level')]},
				4: {title: _('Density'), DP: 7,
					A: [_('Have 3 stars with 5 or more population each')],
					B: [_('1 DP per star you own with 4+ population')]},
				5: {title: _('Diplomatic'), DP: 14,
					A: [_('Have Spirituality level 4 or higher, own the center star of the center sector and be at peace with every player')],
					B: [_('2 DP per other player’s home star where you have a ship (including automa)'),
						_('1 DP per Spirituality level')]},
				6: {title: _('Economic'), DP: 7,
					A: [_('Build 10 ships in a single Build Ships growth action'),
						_('Any ships built as the direct result of star people special effects (e.g. STS Rogue AI) do not count for fulfilling this'),
						_('Play this card when this happens')],
					B: [_('1 DP per Asteroid system where you have a ship'),
						_('1 DP per Robotics level')]},
				7: {title: _('Etheric'), DP: 8,
					A: [_('Have a ship each in 4 nebula hexes at the start of your movement')],
					B: [_('STO: 1 DP per Spirituality level / STS: 1 DP per Military level')]},
				8: {title: _('Exploratory'), DP: 13,
					A: [_('Have Propulsion level 4 or higher, have a ship and a star each in 4 sectors')],
					B: [_('1 DP per sector with a ship of yours'),
						_('1 DP per Propulsion level')]},
				9: {title: _('General Scientific'), DP: 9,
					A: [_('Have a total of 16 technology levels')],
					B: [_('2 DP × your lowest technology level')]},
				10: {title: _('Military'), DP: 10,
					A: [_('Have ships totaling 120 in CV (not counting bonuses of any kind)'),
						_('Reveal enough ships to prove this'),
						_('If you play this card during a battle, all your ships in that battle still count toward the total (even if they would be destroyed)')],
					B: [_('2 DP per sector where you are the only player with a fleet'),
						_('1 DP per Military level')]},
				11: {title: _('Spatial'), DP: 11,
					A: [_('Own 10 stars')],
					B: [_('2 DP per 3 stars you own'),
						_('1 DP per Propulsion level')]},
				12: {title: _('Special Scientific'), DP: 11,
					A: [_('Have level 6 in 1 technology field and level 5 or higher in another field')],
					B: [_('1 DP × your highest technology level')]}
			};
//
			this.TECHNOLOGIES = {
				Military: {
					1: [_('The combat value (CV) of each ship is 1')],
					2: [_('CV of each ship is 1'),
						_('You get 1 advanced fleet tactic')],
					3: [_('CV of each ship is 2')],
					4: [_('CV of each ship is 3'),
						_('You get 1 additional advanced fleet tactic')],
					5: [_('CV of each ship is 6')],
					6: [_('CV of each ship is 10'),
						_('You get 3 additional advanced fleet tactics')],
					'6+': [
						_('You may cancel a growth action counter (solo variant: growth action sentence other than “spawn ships”) of your choice of a player you are “at war” with, which has not been played yet'),
						_('Flip the counter face down to mark this'),
						_('Any DP losses incurred for this action are not reverted in this case'),
						_('This counts as “blocking”, so STS players may declare war in order to do this on a player and players with Spirituality level 5 or 6 are immune to this')
					]
				},
				Spirituality: {
					1: [''],
					2: [_('You may do 1 remote view per round')],
					3: [_('You may do 2 remote views per round')],
					4: [_('You may do 3 remote views per round'),
						_('You may trade technologies without being in contact')],
					5: [_('You may do 4 remote views per round'),
						_('You may trade technologies without being in contact'),
						_('Hostile ships cannot block you')],
					6: [_('You may do an unlimited number of remote views per round'),
						_('You may trade technologies without being in contact'),
						_('Hostile ships cannot block you'),
						_('Any population you lose is put to the side (“ascends”) instead of returning to the population track')],
					'6+': [_('You may select and execute an additional growth action counter (at no cost)'),
						_('You may also exchange a domination card')]
				},
				Propulsion: {
					1: [_('Ship range is 3')],
					2: [_('Ship range is 4')],
					3: [_('Ship range is 4'),
						_('You can use Stargate 1 connections')],
					4: [_('Ship range is 5'),
						_('You can use Stargate 1 connections')],
					5: [_('Ship range is 5'),
						_('You can use Stargate 2 connections'),
						_('You may enter Neutron Star hexes'),
						_('You can teleport 1 population disc (as a free action in growth phase, blockable).')],
					6: [_('You can move your ships anywhere, including Neutron Star hexes'),
						_('You can teleport up to 3 population discs (as a free action in growth phase, blockable)')],
					'6+': [_('You get 2 free Gain Star actions')]
				},
				Robotics: {
					1: [''],
					2: [_('Add 1 ship when doing Build Ships')],
					3: [_('Add 3 ships when doing Build Ships')],
					4: [_('Add 5 ships when doing Build Ships'),
						_('Place new ships at any non-blocked stars of yours with 3+ population')],
					5: [_('Add 7 ships when doing Build Ships'),
						_('Place new ships at any non-blocked stars of yours with 2+ population'),
						_('During growth phase, you may select 2 square counters, you lose 2 DP if you do this')],
					6: [_('Add 10 ships when doing Build Ships'),
						_('Place new ships at any non-blocked stars of yours'),
						_('During growth phase, you may select 2 square counters without losing DP for doing this')],
					'6+': [
						_('You may get an additional level in the other technology field of this research action and lose 2 DP, if you do so'),
						_('If that field is already at level 6 you get the according research+ effect instead')]
				},
				Genetics: {
					1: [''],
					2: [_('You get 1 bonus population when doing Grow Population')],
					3: [_('You get 2 bonus population when doing Grow Population')],
					4: [_('You get 3 bonus population when doing Grow Population')],
					5: [_('You get 4 bonus population when doing Grow Population'),
						_('Only lose 2 DP per additional growth action counter selected')],
					6: [_('You get 6 bonus population when doing Grow Population'),
						_('Only lose 1 DP per additional growth action counter selected')],
					'6+': [
						_('You get a free Grow Population action with 2 additional bonus population')]
				}
			};
//
			this.GROWTHACTIONS = {
				'changeTurnOrderUp': [_('Change Turn Order UP'),
					('First, all players who selected an “up” (green arrow pointing up) turn order change counter switch to one position earlier in turn order.<BR>Do this by starting with the smallest number and then continuing in numerical order.<BR>Each such player exchanges their octagonal turn order counter with the player who has the next smaller number.<BR>For a player who already had the number 1 of the turn order at the start of the phase such a counter does nothing though.')
				],
				'changeTurnOrderDown': [_('Change Turn Order DOWN'),
					_('Secondly, all players who selected a “down” (red arrow pointing down) turn order change counter switch to one position later in turn order (swapping with the next greater number).<BR>Do this as above, but starting with the greatest number and then continuing in reverse numerical order.<BR>For a player who already had the greatest turn order number at the start of the phase such a counter does nothing though.')
				],
				'buildShips': [
					_('Build Ships'),
					_('Get additional ships as indicated by your population track, plus 1 per asteroid system where you have a ship and your bonus from Robotics.<BR>Place at any of your stars with 4+ population.')
				],
				'spawnShips': [
					_('Spawn Ships'),
					_('“Spawning ships” is a special type of growth action that only the automas have.')],
				'gainStar': [
					_('Gain Star'),
					_('Take one star of your choice where you have the required number of ships as shown by the table on your star people tile.')],
				'gainStar+': [
					_('Gain Star (center sector only)'),
					_('Take one star of your choice where you have the required number of ships as shown by the table on your star people tile.')],
				'growPopulation': [
					_('Grow Population'),
					_('First get 1 additional population at every star below its limit (=distance to nearest owned star).<BR>Then get bonus population as per Genetics.')],
				'growPopulation+': [
					_('Grow Population (2 additional bonus population)'),
					_('First get 1 additional population at every star below its limit (=distance to nearest owned star).<BR>Then get bonus population as per Genetics.')],
				'research': [
					_('Research'),
					_('You must also select a square technology counter before revealing.<BR>Go up 1 level in the selected technology.')],
				'switchAlignment': [
					_('Switch Alignment'),
					_('Happens immediately when the action is revealed.<BR>Flip your star people tile over to the other side.<BR>You are automatically at peace with everyone then.')
				]
			};
//
			this.RELICS = {
				0: [_('Ancient Pyramids'), [
						_('The player who owns this star gets 1 additional remote view per round<BR>This may be used on a hidden thing as normal or to view any fleet (regardless of Spirituality levels)'),
						_('Also, whenever this player does Grow Population place 1 bonus population here')]],
				1: [_('Ancient Technology: Genetics'), [_('The player who first gains this star immediately gets 1 level in Genetics')]],
				2: [_('Ancient Technology: Military'), [_('The player who first gains this star immediately gets 1 level in Military')]],
				3: [_('Ancient Technology: Propulsion'), [_('The player who first gains this star immediately gets 1 level in Propulsion')]],
				4: [_('Ancient Technology: Robotics'), [_('The player who first gains this star immediately gets 1 level in Robotics')]],
				5: [_('Ancient Technology: Spirituality'), [_('The player who first gains this star immediately gets 1 level in Spirituality')]],
				6: [_('Buried Ships'), [
						_('The player who first gains this star immediately gets 3 ships that are placed there'),
						_('Use the same rules for placing these ships as when doing the Build Ships action'),
						_('This effect cannot be blocked though')]],
				7: [_('Planetary Death Ray'), [
						_('During the movement of the move/combat phase the player owning this star may destroy 1 ship or population disc (but not a home star miniature) of a player they are “at war” with, within a distance of 3 hexes to this star'),
						_('If a star loses its last population, it becomes “uninhabited”'),
						_('If a fleet loses its last ship, it is dissolved'),
						_('The removed ship or population may count for scoring, depending upon the galactic story')]],
				8: [_('Defense Grid'), [
						_('Any player conquering or liberating this star needs 8 ships more than usual to do that'),
						_('This only applies when the star is owned by a player'),
						_('Once this star has been taken by a player for the first time, all ships and population discs here are immune to the “Planetary Death Ray”, regardless of ownership')
					]],
				9: [_('Super-Stargate'), [
						_('The player who owns this star may use stargate movement from any star of theirs to this one or vice versa (regardless of their level in Propulsion)'),
						_('If the player has Propulsion 5 this movement cannot be blocked (in either direction)')]]
			};
//
			this.RANKINGS = {0: _('lunar'), 1: _('planetary'), 2: _('stellar'), 3: _('galactic'), 4: _('cosmic')};
//
			this.ERAtechnologyTooltips = new dijit.Tooltip({
				showDelay: 500, hideDelay: 0,
				getContent: (node) =>
				{
					const technology = dojo.getAttr(node, 'technology');
					const technologyLevel = dojo.getAttr(node, 'level');
//
					let html = `<H1 style='font-family:ERA;'>${_(technology)}</H1>`;
					html += '<div style="display:grid;grid-template-columns:1fr 5fr;max-width:50vw;outline:1px solid white;">';
					html += '<div style="padding:12px;text-align:center;outline:1px solid grey;font-style:italic;font-weight:bold;">' + _('Level') + '</div>';
					html += '<div style="padding:12px;text-align:center;outline:1px solid grey;font-style:italic;font-weight:bold;">' + _('Effect') + '</div>';
					for (let [level, strings] of Object.entries(this.TECHNOLOGIES[technology]))
					{
						html += `<div style="padding:12px;text-align:center;outline:1px solid grey;${level <= technologyLevel ? 'font-weight:bold;' : ''}">${level}</div>`;
						html += `<div style="padding:12px;outline:1px solid grey;${level === technologyLevel ? 'font-weight:bold;' : 'color:lightgrey;'}">`;
						for (let string of strings) html += '<div style="text-align:justify;">' + string + '</div>';
						html += '</div>';
					}
					return html;
				}
			});
//
			this.ERAdominationCards = new dijit.Tooltip({
				showDelay: 500, hideDelay: 0,
				getContent: (node) =>
				{
					let html = `<div style='display:grid;gap: 5px 5px;grid-template-columns:auto [title] repeat(6, auto [A]) repeat(6, auto [B]) auto [description];align-items:center;'>`;
					html += `<div style='display:grid;gap: 5px 5px;grid-template-columns:auto [title] repeat(6, auto [A]) repeat(6, auto [B]) auto [description];align-items:center;'>`;
//
					html += `<div style='grid-line:header;grid-column:title;'><B>${_('Domination cards')}</B></div>`;
					html += `<div style='grid-line:header;grid-column:A 1 / A 6;justify-self:center'><B>A</B></div>`;
					html += `<div style='grid-line:header;grid-column:B 1 / B 6;justify-self:center'><B>B</B></div>`;
//
					for (let domination in this.DOMINATIONS)
					{
						const played = dojo.query(`.ERAtechTrack>.ERAdominationCard[domination='${domination}']`).length;
//
						html += `<div style='grid-line:${domination};grid-column:title;color:${played ? 'grey' : 'white'}'><B>${this.DOMINATIONS[domination].title}</B></div>`;
//
						if (!played)
						{
							for (let faction of Object.values(gamedatas.factions))
							{
								if (faction.player_id > 0)
								{
									const A = this.gamedatas.factions[faction.color].scoring[domination].A;
									if (A) html += `<div style='grid-line:${domination};grid-column:A ${faction.order};color:#${faction.color};text-align:center;font-size:${2 * A + 10}pt'>${A}</div>`;
									else html += `<div style='grid-line:${domination};grid-column:A ${faction.order};color:#${faction.color};text-align:center;'>🗙</div>`;
								}
							}
//
							for (let faction of Object.values(gamedatas.factions))
							{
								if (faction.player_id > 0)
								{
									html += `<div style='grid-line:${domination};grid-column:B ${faction.order};color:#${faction.color};'>`;
									for (let score of this.gamedatas.factions[faction.color].scoring[domination].B)
										html += `<div style='font-family:ERA;font-size:${score * 2 + 10}pt;text-align:center;height:20pt;line-height:20pt;vertical-align:middle;'>${score}</div>`;
									html += `</div>`;
								}
							}
						}
//
						html += `<div style='grid-line:${domination};grid-column:description;font-size:x-small;'>`;
						for (let text of this.DOMINATIONS[domination].B) html += `<div>${text}</div>`;
						html += `</div>`;
//
					}
					html += `</div>`;
					return html;
				}
			});
//
// Animations speed
//
			DELAY = DELAYS[this.prefs[100].value];
			document.documentElement.style.setProperty('--DELAY', DELAY);
			dojo.query('.preference_control').connect('onchange', this, 'updatePreference');
//
			this.players = {};
			for (let faction of Object.values(gamedatas.factions)) this.players[faction.player_id] = faction.color;
			this.color = this.player_id in this.players ? this.players[this.player_id] : null;
//
// Setup game Board
//
			this.board = new Board(this);
			this.factions = new Factions(this);
			this.counters = new Counters(this);
			this.ships = new Ships(this);
//
			dojo.addClass('ebd-body', 'ERAextended');
//
// Setup factions
//
			for (let faction of Object.values(gamedatas.factions))
			{
				if (faction.player_id <= 0)
				{
					let node = dojo.place(`<div id='overall_player_board_${faction.player_id}' class='player-board' id='player_board_${faction.player_id}'/></div>`, 'player_boards');
					dojo.place(`<div class='player-name' id='player_name_${faction.player_id}'><span style='color:#${faction.color}'>${{0: _('Automa'), 1: _('Genetic Farmers'), 2: _('Slavers')}[ -faction.player_id]}<span></div>`, node);
					dojo.place(`<div id='player_board_${faction.player_id}' class='player-board_content' id='player_board_${faction.player_id}'/></div>`, node);
				}
//
// Setup player panels
//
				let nodeCounters = dojo.place(`<div class='ERAcounters' id='ERAcounters-${faction.color}'></div>`, `player_board_${faction.player_id}`, 2);
//
				let nodeStatus = dojo.place(`<div id='ERAstatus-${faction.color}' class='ERAstatus'></div>`, nodeCounters, 'after');
//
				dojo.place(`<div id='ERAplayerDominationCards-${faction.color}' class='ERAdominationCards'></div>`, nodeStatus);
//
				dojo.place(`<div class='ERAsmall ERAcounter ERAorder' id='ERAorder-${faction.color}' faction='${faction.color}'></div>`, nodeStatus);
				this.addTooltip(`ERAorder-${faction.color}`, _('Turn order'), '');
//
				if ($(container = `ERAboardShips-${faction.color}`))
				{
					dojo.place(`<div style='color:white;font-size:xx-large;'>(<span class='ERAships' faction='${faction.color}'>${faction.ships}</span>)</div>`, container);
					const node = dojo.place(this.format_block('ERAship', {id: 0, color: faction.color, location: ''}), container);
					dojo.style(node, {
						'transform': 'scale(50%)',
						'transform-origin': 'left'
					});
				}
				if ($(container = `ERAboardOrder-${faction.color}`)) dojo.place(`<div class='ERAcounter ERAorder' faction='${faction.color}'></div>`, container);
				if ($(container = `ERAboardStatus-${faction.color}`))
				{
					for (let otherFaction of Object.values(gamedatas.factions))
					{
						if (otherFaction.color !== faction.color)
						{
							dojo.place(`<div class='ERAcounter ERAcounter-${otherFaction.color} ERAcounter-peace' color='${faction.color}' on='${otherFaction.color}'></div>`, container);
							dojo.place(`<div class='ERAcounter ERAcounter-${otherFaction.color} ERAcounter-war' color='${faction.color}' on='${otherFaction.color}'></div>`, container);
						}
					}
				}
//
				let nodeFaction = dojo.place(this.format_block('ERAfaction', {color: faction.color}), nodeStatus, 'after');
//
				let nodeStarPeople = dojo.place(this.format_block('ERAstarPeople', {starpeople: faction.starPeople}), nodeFaction);
				dojo.style(nodeStarPeople, 'flex', '1 1 50%');
//
				let nodeTechnologies = dojo.place(this.format_block('ERAtechnologies', {color: faction.color}), nodeFaction);
				let nodePopulation = dojo.place(`<div>${'Population discs'} : <span id='ERApopulation-${faction.color}'>?</span>/39</div>`, nodeTechnologies);
// Farmers
				if (+faction.player_id === -1) dojo.style(nodePopulation, 'display', 'none');
// Slavers
				if (+faction.player_id === -2)
				{
					const node = dojo.place(`<div id='ERAoffboard' title='${_('Slavers’ offboard power track')}'><img style='width:100%;' src='${g_gamethemeurl}img/offboard.jpg' draggable='false'/></div>`, nodeFaction, 'after');
					dojo.connect(node, 'click', (event) => {
						dojo.stopEvent(event);
						this.focus(event.currentTarget);
					});
					dojo.connect(node, 'transitionend', () => dojo.style(node, {'pointer-events': '', 'z-index': ''}));
				}
//
				for (let technology of ['Military', 'Spirituality', 'Propulsion', 'Robotics', 'Genetics'])
				{
					const node = dojo.place(`<div id='ERAtech-${faction.color}-${technology}' technology='${technology}' level='0'><span class='ERAtechnology'>?</span><div class='ERAsmallerTechnology'><div class='ERAcounter ERAcounter-technology' counter='${technology}'/></div>`, nodeTechnologies);
					this.ERAtechnologyTooltips.addTarget(node);
				}
//
				dojo.connect($(`player_board_${faction.player_id}`), 'click', () => this.board.home(faction.player_id));
			}
//
// Setup factions peace & war
//
			for (let faction of Object.values(gamedatas.factions))
			{
				const nodeStatus = `ERAstatus-${faction.color}`;
				const name = $(`player_name_${faction.player_id}`).children[0].outerHTML;
				for (let otherFaction of Object.values(gamedatas.factions))
				{
					if (otherFaction.color !== faction.color)
					{
						const otherName = $(`player_name_${otherFaction.player_id}`).children[0].outerHTML;
						const nodePeace = dojo.place(`<div id='ERApeace-${faction.color}-${otherFaction.color}' class='ERAsmall ERAcounter ERAcounter-${otherFaction.color} ERAcounter-peace' color='${faction.color}' on='${otherFaction.color}'></div>`, nodeStatus);
						dojo.toggleClass(nodePeace, 'ERAselectable', this.color === faction.color);
						if (this.player_id === +faction.player_id) dojo.connect(nodePeace, 'click', (event) => {
								dojo.stopEvent(event);
								if (+this.gamedatas.GODMODE === 1) return this.action('GODMODE', {god: JSON.stringify({action: 'declareWar', color: faction.color, on: otherFaction.color})});
								if (this.isCurrentPlayerActive() && this.gamedatas.gamestate.possibleactions.includes('declareWar'))
								{
									this.confirmationDialog(dojo.string.substitute(_('Declare war on ${on}'), {on: `<span style='background:black'>${otherName}</span>`}), () =>
									{
										this.action('declareWar', {color: faction.color, on: otherFaction.color});
									});
								}
							});
						this.addTooltip(`ERApeace-${faction.color}-${otherFaction.color}`, dojo.string.substitute(_('${player1} at <B>peace</B> with ${player2}'), {player1: name, player2: otherName}), this.player_id === +faction.player_id ? _('Click to declare War') : '');
						const nodeWar = dojo.place(`<div id='ERAwar-${faction.color}-${otherFaction.color}'  class='ERAsmall ERAcounter ERAcounter-${otherFaction.color} ERAcounter-war' color='${faction.color}' on='${otherFaction.color}'></div>`, nodeStatus);
						dojo.toggleClass(nodeWar, 'ERAselectable', this.color === faction.color);
						if (this.player_id === +faction.player_id) dojo.connect(nodeWar, 'click', (event) => {
								dojo.stopEvent(event);
								if (+this.gamedatas.GODMODE === 1) return this.action('GODMODE', {god: JSON.stringify({action: 'declarePeace', color: faction.color, on: otherFaction.color})});
								if (this.isCurrentPlayerActive() && this.gamedatas.gamestate.possibleactions.includes('declarePeace'))
								{
									this.confirmationDialog(dojo.string.substitute(_('Propose peace to ${on}'), {on: `<span style='background:black'>${otherName}</span>`}), () =>
									{
										this.action('declarePeace', {color: faction.color, on: otherFaction.color});
									});
								}
							});
						this.addTooltip(`ERAwar-${faction.color}-${otherFaction.color}`, dojo.string.substitute(_('${player1} at <B>war</B> with ${player2}'), {player1: name, player2: otherName}), this.player_id === +faction.player_id ? _('Click to propose Peace') : '');
					}
				}
				this.factions.update(faction);
			}
//
//	Focus to examine emergency reserve
//
			dojo.query(`.ERAemergencyReserve`).forEach((node) =>
			{
				dojo.connect(node, 'click', (event) => {
					dojo.stopEvent(event);
					this.focus(event.currentTarget);
				});
				dojo.connect(node, 'transitionend', () => dojo.style(node, {'pointer-events': '', 'z-index': ''}));
			});
//
//	Focus to examine and swap Star People tiles
//
			dojo.query(`.ERAstarPeople`).forEach((node) =>
			{
				dojo.connect(node, 'click', (event) =>
				{
					dojo.stopEvent(event);
					if (dojo.hasClass(event.currentTarget, 'ERAfocus'))
					{
						if (!dojo.hasClass(event.currentTarget, 'ERA-STO')) dojo.setAttr(event.currentTarget.querySelector('img'), 'src', dojo.getAttr(event.currentTarget, 'STS'));
						else dojo.setAttr(event.currentTarget.querySelector('img'), 'src', dojo.getAttr(event.currentTarget, 'STO'));
						dojo.toggleClass(event.currentTarget, 'ERA-STO');
					}
					else this.focus(event.currentTarget);
				});
				dojo.connect(node, 'transitionend', (event) =>
				{
					if (dojo.hasClass(event.currentTarget, 'ERA-STS')) dojo.setAttr(event.currentTarget.querySelector('img'), 'src', dojo.getAttr(event.currentTarget, 'STS'));
					else dojo.setAttr(event.currentTarget.querySelector('img'), 'src', dojo.getAttr(event.currentTarget, 'STO'));
					dojo.toggleClass(event.currentTarget, 'ERA-STO', dojo.hasClass(event.currentTarget, 'ERA-STS'));
					dojo.style(event.currentTarget, {'pointer-events': '', 'z-index': ''});
				});
			});
//
// Gray pawn
//
			dojo.place("<div class='ERApawn' id='ERApawn'></div>", 'ERA-DP');
			this.updateRound(gamedatas.round);
//
// Place ships
//
			for (let ship of Object.values(gamedatas.ships)) this.ships.place(ship);
//
// Place counters
//
			for (let counter of Object.values(gamedatas.counters)) this.counters.place(counter);
//
// Domination deck
//
			const node = dojo.place(this.format_block('ERAdominationCard', {id: 'dominationDeck', index: '', domination: '', owner: ''}), `ERA-DominationDeck`);
			dojo.setAttr(node.querySelector('img'), 'src', `${g_gamethemeurl}img/dominationCards/back.jpg`);
			dojo.connect($('ERA-DominationDeck'), 'click', (event) => {
				dojo.stopEvent(event);
				if (this.gamedatas.gamestate.name === 'remoteViewing') this.remoteViewing('dominationCard', event.currentTarget);
			});
			this.ERAdominationCards.addTarget($('ERA-DominationDeck'));
//
// Played Domination Cards
//
			for (let card of Object.values(gamedatas.A)) this.playDomination(card, 'A');
			for (let card of Object.values(gamedatas.B)) this.playDomination(card, 'B');
//
// Reveal hidden things
//
			if (this.player_id in this.players)
			{
				const faction = gamedatas.factions[this.players[this.player_id]];
				if ('revealed' in faction)
				{
					for (let star in faction.revealed.stars) this.counters.flip({id: star, type: faction.revealed.stars[star]});
					for (let relic in faction.revealed.relics) this.counters.flip({id: relic, type: faction.revealed.relics[relic]});
					for (let fleet in faction.revealed.fleets) this.ships.reveal({id: fleet, fleet: faction.revealed.fleets[fleet].fleet, ships: faction.revealed.fleets[fleet].ships});
				}
			}
//
			if (gamedatas.peace) for (let color of gamedatas.peace) this.peace(color);
//

			this.setupNotifications();
//
			console.log("Ending game setup");
		},
		onEnteringState: function (stateName, state)
		{
			console.log('Entering state: ' + stateName, state.args);
//
			if (!(state.args)) return;
			dojo.query('.ERAship', 'ERAplayArea').forEach((node) => dojo.setAttr(node, 'draggable', +this.gamedatas.GODMODE === 1));
			dojo.toggleClass('page-title', 'GODMODE', +this.gamedatas.GODMODE === 1);
//
			dojo.query('.ERAcounters').empty();
			if ('counters' in state.args)
			{
				for (color in state.args.counters)
				{
					for (const index in state.args.counters[color].available)
					{
						const counter = state.args.counters[color].available[index];
						switch (counter)
						{
							case 'Military':
							case 'Spirituality':
							case 'Propulsion':
							case 'Robotics':
							case 'Genetics':
								node = dojo.place(`<div id='availableGrowthAction-${color}-${index}' class='ERAsmallTechnology'><div class='ERAcounter ERAcounter-technology' counter='${counter}' color='${color}'/></div>`, `ERAcounters-${color}`);
								this.addTooltip(node.id, _(counter), '');
								break;
							default:
								node = dojo.place(`<div id='availableGrowthAction-${color}-${index}' class='ERAavailableGrowthAction ERAsmallGrowth'><div class='ERAcounter ERAcounter-${color} ERAcounter-growth' counter='${counter}' color='${color}'/></div>`, `ERAcounters-${color}`);
								dojo.connect(node, 'click', (event) => {
									dojo.stopEvent(event);
									const counter = event.currentTarget.children[0];
									if (this.isCurrentPlayerActive() && dojo.hasClass(counter, 'ERAselectable'))
									{
										this.action('researchPlus', {color: this.color, technology: 'Military', otherColor: dojo.getAttr(counter, 'color'), growthAction: dojo.getAttr(counter, 'counter')});
									}
								});
								this.addTooltip(node.id, ...this.GROWTHACTIONS[(counter === 'buildShips' && this.gamedatas.factions[color].player_id < 0) ? 'spawnShips' : counter]);
						}
					}
					for (const index in state.args.counters[color].used)
					{
						const counter = state.args.counters[color].used[index];
						switch (counter)
						{
							case 'teleportPopulation':
								break;
							case 'Military':
							case 'Spirituality':
							case 'Propulsion':
							case 'Robotics':
							case 'Genetics':
								node = dojo.place(`<div id='usedGrowthAction-${color}-${index}' class='ERAsmallTechnology' style='filter:opacity(75%);'><div class='ERAcounter ERAcounter-technology' counter='${counter}' color='${color}'/></div>`, `ERAcounters-${color}`);
								dojo.connect(node, 'click', (event) => dojo.stopEvent(event));
								this.addTooltip(node.id, _(counter), '');
								break;
							default:
								node = dojo.place(`<div id='usedGrowthAction-${color}-${index}' class='ERAsmallGrowth' style='filter:opacity(75%);'><div class='ERAcounter ERAcounter-${color} ERAcounter-growth' counter='${counter}' color='${color}'/></div>`, `ERAcounters-${color}`);
								dojo.connect(node, 'click', (event) => dojo.stopEvent(event));
								this.addTooltip(node.id, ...this.GROWTHACTIONS[(counter === 'buildShips' && this.gamedatas.factions[color].player_id < 0) ? 'spawnShips' : counter]);
						}
					}
				}
			}
//
			if ('_private' in state.args && 'starPeople' in state.args._private)
			{
				dojo.place(this.format_block('ERAchoice', {}), 'game_play_area');
				for (let starPeople of state.args._private.starPeople)
				{
					let node = dojo.place(this.format_block('ERAstarPeople', {starpeople: starPeople}), 'ERAchoice');
					dojo.setStyle(node, 'max-width', '500px');
					dojo.setAttr(node, 'STO', `${g_gamethemeurl}img/starPeoples/${starPeople}.STO.jpg`);
					dojo.setAttr(node, 'STS', `${g_gamethemeurl}img/starPeoples/${starPeople}.STS.jpg`);
					dojo.setAttr(node.querySelector('img'), 'src', dojo.getAttr(node, state.args._private.alignment ? 'STS' : 'STO'));
//
					dojo.connect(node, 'click', (event) => {
						if (this.isCurrentPlayerActive())
						{
							if (dojo.hasClass(event.currentTarget, 'ERAselected'))
							{
								dojo.toggleClass(event.currentTarget, 'ERA-STS');
								if (dojo.hasClass(event.currentTarget, 'ERA-STS')) dojo.setAttr(event.currentTarget.querySelector('img'), 'src', dojo.getAttr(event.currentTarget, 'STS'));
								else dojo.setAttr(event.currentTarget.querySelector('img'), 'src', dojo.getAttr(event.currentTarget, 'STO'));
							}
							dojo.query('#ERAchoice .ERAstarPeople').removeClass('ERAselected');
							dojo.query('#ERAchoice .ERAstarPeople>img').style('border-color', '');
							dojo.style(event.currentTarget.querySelector('img'), 'border-color', '#' + state.args._private.color);
							dojo.addClass(event.currentTarget, 'ERAselected');
							dojo.removeClass('ERAconfirmButton', 'disabled');
						}
						else
						{
//							dojo.toggleClass(event.currentTarget, 'ERA-STS');
//							if (dojo.hasClass(event.currentTarget, 'ERA-STS')) dojo.setAttr(event.currentTarget.querySelector('img'), 'src', dojo.getAttr(event.currentTarget, 'STS'));
//							else dojo.setAttr(event.currentTarget.querySelector('img'), 'src', dojo.getAttr(event.currentTarget, 'STO'));
						}
					});
				}
			}
//
			if ('_private' in state.args && 'counters' in state.args._private)
			{
				const root = dojo.place("<div id='ERAchoice' style='position:absolute;top:10vh;display:flex;flex-flow:column;width:100%;gap:2vh;pointer-events:none;'></div>", 'game_play_area');
				dojo.style(root, 'top', '2vh');
				dojo.style(root, 'right', '2vh');
//
				const growthNode = dojo.place("<div style='display:flex;flex-flow:row wrap;justify-content:right;margin-right:1%;gap:1%;'></div>", root);
				const technologyNode = dojo.place("<div style='display:flex;flex-flow:row wrap;justify-content:right;margin-right:1%;gap:1%;'></div>", root);
				const turnOrderNode = dojo.place("<div style='display:flex;flex-flow:row wrap;justify-content:right;margin-right:1%;gap:1%;'></div>", root);
//
				for (const index in state.args._private.counters)
				{
					const counter = state.args._private.counters[index];
					switch (counter)
					{
						case 'Military':
						case 'Spirituality':
						case 'Propulsion':
						case 'Robotics':
						case 'Genetics':
							{
								const container = dojo.place('<div></div>', technologyNode);
								const node = dojo.place(this.format_block('ERAcounter', {id: 'growthAction-' + index, color: state.args._private.color, type: 'technology', location: ''}), container);
								dojo.setAttr(node, 'counter', counter);
								dojo.addClass(node, 'ERAselectable');
								this.addTooltip(node.id, _(counter), '');
								dojo.connect(node, 'click', (event) => {
									dojo.stopEvent(event);
									if (this.isCurrentPlayerActive())
									{
										if (stateName === 'selectCounters')
										{
											if (state.args._private.square > 1 || +this.gamedatas.GODMODE === 1)
											{
												dojo.toggleClass(event.currentTarget, 'ERAselected');
												dojo.toggleClass('ERAselectButton', 'disabled', !this.checkGrowthActions());
											}
											else
											{
												dojo.query('#ERAchoice .ERAcounter-turnOrder.ERAselected').removeClass('ERAselected');
												dojo.query('#ERAchoice .ERAcounter-technology.ERAselected').removeClass('ERAselected');
												dojo.toggleClass(event.currentTarget, 'ERAselected');
												dojo.toggleClass('ERAselectButton', 'disabled', !this.checkGrowthActions());
											}
										}
										if (stateName === 'individualChoice') this.action('individualChoice', {color: this.color, technology: counter});
										if (stateName === 'stealTechnology') this.action('stealTechnology', {color: this.color, technology: counter});
									}
								});
							}
							break;
						case 'changeTurnOrderUp':
						case 'changeTurnOrderDown':
							{
								const container = dojo.place('<div></div>', turnOrderNode);
								const node = dojo.place(this.format_block('ERAcounter', {id: 'growthAction-' + index, color: state.args._private.color, type: 'turnOrder', subtype: counter, location: ''}), container);
								dojo.setAttr(node, 'counter', counter);
								dojo.addClass(node, 'ERAselectable');
								this.addTooltip(node.id, ...this.GROWTHACTIONS[counter]);
								dojo.connect(node, 'click', (event) => {
									dojo.stopEvent(event);
									if (this.isCurrentPlayerActive())
									{
										if (state.args._private.square > 1 || +this.gamedatas.GODMODE === 1)
										{
											if (!dojo.hasClass(event.currentTarget, 'ERAselected')) dojo.query('#ERAchoice .ERAcounter-turnOrder.ERAselected').removeClass('ERAselected');
											dojo.toggleClass(event.currentTarget, 'ERAselected');
											dojo.toggleClass('ERAselectButton', 'disabled', !this.checkGrowthActions());
										}
										else
										{
											dojo.query('#ERAchoice .ERAcounter-turnOrder.ERAselected').removeClass('ERAselected');
											dojo.query('#ERAchoice .ERAcounter-technology.ERAselected').removeClass('ERAselected');
											dojo.toggleClass(event.currentTarget, 'ERAselected');
											dojo.toggleClass('ERAselectButton', 'disabled', !this.checkGrowthActions());
										}
									}
								});
							}
							break;
						default:
						{
							const container = dojo.place("<div></div>", growthNode);
							const node = dojo.place(this.format_block('ERAcounter', {id: 'growthAction-' + index, color: state.args._private.color, type: 'growth', subtype: counter, location: ''}), container);
							dojo.setAttr(node, 'counter', counter);
							dojo.addClass(node, 'ERAselectable');
							if (counter === 'gainStar+' && stateName === 'selectCounters') dojo.addClass(node, 'ERAdisabled');
							this.addTooltip(node.id, ...this.GROWTHACTIONS[counter]);
							dojo.connect(node, 'click', (event) => {
								dojo.stopEvent(event);
								if (this.isCurrentPlayerActive())
								{
									if (stateName === 'selectCounters')
									{
										dojo.toggleClass(event.currentTarget, 'ERAselected');
										dojo.toggleClass('ERAselectButton', 'disabled', !this.checkGrowthActions());
									}
									if (stateName === 'resolveGrowthActions')
									{
										switch (counter)
										{
											case 'switchAlignment':
												this.action('switchAlignment', {color: this.color});
												break;
											case 'research':
												const technologies = dojo.query('.ERAcounter-technology', 'ERAchoice').reduce((counters, node) => [...counters,
														node.getAttribute('counter')], []);
												this.action('research', {color: this.color, technologies: JSON.stringify(technologies)});
												break;
											case 'growPopulation':
												this.setClientState('growPopulation', {counter: node.id, possibleactions: ['growPopulation'
													], descriptionmyturn: _('${you} may add one population disc to every star that is below its “growth limit”')});
												break;
											case 'growPopulation+':
												state.args._private.bonusPopulation += 2;
												this.setClientState('growPopulation+', {counter: node.id, possibleactions: ['growPopulation'],
													args: state.args,
													descriptionmyturn: _('${you} may add one population disc to every star that is below its “growth limit”')});
												break;
											case 'gainStar':
												this.setClientState(counter, {counter: node.id, possibleactions: ['gainStar'], descriptionmyturn: _('${you} may choose to populate or take over a star')});
												break;
											case 'gainStar+':
												this.setClientState(counter, {counter: node.id, possibleactions: ['gainStar'], descriptionmyturn: _('${you} may choose to populate or take over a star (center sector only)')});
												break;
											case 'buildShips':
												this.setClientState(counter, {counter: node.id, possibleactions: ['buildShips'
													], descriptionmyturn: dojo.string.substitute(_('${you} get ${SHIPS} new ship(s)'), {you: '${you}', SHIPS: state.args._private.newShips})});
												break;
										}
									}
									if (stateName === 'researchPlus') this.action('researchPlus', {color: this.color, technology: 'Spirituality', growthAction: counter});
								}
							});
						}
					}
				}
				if (stateName === 'individualChoice') dojo.query('#ERAchoice .ERAcounter-technology').addClass('ERAselectable');
//
				if ('counter' in state)
				{
					window.setTimeout(() => {
						dojo.addClass(state.counter, 'ERAselectable');
						dojo.addClass(state.counter, 'ERAselected');
						if (stateName === 'research') dojo.query('#ERAchoice .ERAcounter-technology').addClass('ERAselected');
					}, 1);
				}
			}
//
			switch (stateName)
			{
				case 'levelOfDifficulty':
				{
					this.levelOfDifficulty = new ebg.popindialog();
					this.levelOfDifficulty.create('ERAlevelOfDifficulty');
					this.levelOfDifficulty.setTitle(_("Level of difficulty"));
//
					html = `<div style='display:grid;grid-template-columns: 2fr 1fr 3fr;column-gap:10px;align-items:center;'>`;
//
					html += `<div></div>`;
					html += `<div style='text-align:center;font-weight:bold;'>${_('High-scores')}</div>`;
					html += `<div></div>`;
//
					html += `<div id='ERA-0' data-level='0' class='ERAbutton bgabutton bgabutton_${true ? 'green' : 'red'}'>${_('Easy')}</div>`;
					html += `<div style='text-align:center;font-weight:bold;'>${state.args.legacy[0] ? state.args.legacy[0] : _('No high-score')}</div>`;
					html += `<div style='font-style:italic;'>${_('+0 ship bonus for Slavers')}</div>`;
//
					html += `<div id='ERA-1' data-level='1' class='ERAbutton bgabutton bgabutton_${state.args.legacy[0] > 0 ? 'green' : 'red'}'>${_('Standard')}</div>`;
					html += `<div style='text-align:center;font-weight:bold;'>${state.args.legacy[1] ? state.args.legacy[1] : _('No high-score')} </div>`;
					html += `<div style='font-style:italic;'>${_('+1 ship bonus for Slavers')}</div>`;
//
					html += `<div id='ERA-2' data-level='2' class='ERAbutton bgabutton bgabutton_${state.args.legacy[1] > 0 ? 'green' : 'red'}'>${_('Hard')}</div>`;
					html += `<div style='text-align:center;font-weight:bold;'>${state.args.legacy[2] ? state.args.legacy[2] : _('No high-score')} </div>`;
					html += `<div style='font-style:italic;'>${_('+2 ship bonus for Slavers<BR>One disc removed from Slavers’ population track')}</div>`;
//
					html += `<div id='ERA-3' data-level='3' class='ERAbutton bgabutton bgabutton_${state.args.legacy[2] > 0 ? 'green' : 'red'}'>${_('Insane')}</div>`;
					html += `<div style='text-align:center;font-weight:bold;'>${state.args.legacy[3] ? state.args.legacy[3] : _('No high-score')} </div>`;
					html += `<div style='font-style:italic;'>${_('+3 ship bonus for Slavers<BR>Two discs removed from Slavers’ population track')}</div>`;
//
					html += `<div id='ERAgoButton' class='bgabutton bgabutton_big bgabutton_blue'>${_('Go to game')}</div>`;
					html += `<div></div>`;
					html += `<div id='ERAmsg' style='color:red;font-weight:bold;'></div>`;
					html += `</div>`;
//
					this.levelOfDifficulty.setContent(html);
					this.levelOfDifficulty.hideCloseIcon();
					this.levelOfDifficulty.show();
//
					let level = 4;
					while (--level > 0) if (state.args.legacy[level - 1] > 0) break;
					dojo.addClass(`ERA-${level}`, 'ERAselected');
//
					this.connectClass('ERAbutton', 'click', (event) => {
//
						dojo.query('.ERAbutton').removeClass('ERAselected');
						dojo.addClass(event.currentTarget, 'ERAselected');
//
						const level = +event.currentTarget.dataset.level;
						const check = (level === 0) || (state.args.legacy[level - 1] > 0);
						$('ERAmsg').innerHTML = check ? '' : _('You must play at least one game at all easier levels');
						dojo.toggleClass('ERAgoButton', 'disabled', !check);
					});
//
					dojo.connect($('ERAgoButton'), 'click', () => {
						const node = $('popin_ERAlevelOfDifficulty').querySelector('.ERAbutton.ERAselected');
						if (node)
						{
							this.action('levelOfDifficulty', {levelOfDifficulty: node.dataset.level});
							this.levelOfDifficulty.destroy();
							delete this.levelOfDifficulty;
						}
					});
//
					break;
				}
				case 'remoteViewing':
					dojo.query('#ERAboard>.ERAcounter-star:not([back]').addClass('ERAselectable').addClass('ERAselected');
					dojo.query('#ERAboard>.ERAcounter-relic:not([back]').addClass('ERAselectable').addClass('ERAselected');
					dojo.query(`#ERAboard>.ERAship[fleet]:not(.ERAship-${state.args.active})`).addClass('ERAselectable').addClass('ERAselected');
					dojo.query('#ERA-DominationDeck>.ERAdominationCard:last-child').addClass('ERAselectable ERAselected');
					break;
//
				case 'fleets':
					dojo.query(`#ERAboard>.ERAship[color=${this.color}]`).addClass('ERAselectable');
					break;
//
				case 'movement':
					dojo.query(`#ERAboard>.ERAship[color=${this.color}]`).addClass('ERAselectable').style('opacity', '75%');
					if (this.isCurrentPlayerActive()) for (const ship in state.args._private.move) dojo.style(`ERAship-${ship}`, 'opacity', '');
					break;
//
				case 'combatChoice':
					{
						const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
						dojo.setStyle(svg, 'position', 'absolute');
						dojo.setStyle(svg, 'left', '0px');
						dojo.setStyle(svg, 'top', '0px');
						dojo.setStyle(svg, 'z-index', '1');
						dojo.setStyle(svg, 'pointer-events', 'all');
						svg.setAttribute("width", 10000);
						svg.setAttribute("height", 10000);
						svg.id = 'ERAcombatChoice';
//
						for (let location of state.args.combatChoice)
						{
							dojo.query(`[location = '${location}']`, 'ERAboard').addClass('ERAselectable');
							svg.appendChild(this.board.drawHexagon(this.board.hexagons[location], "#" + state.args.active + 'C0'));
						}
//
						this.board.board.appendChild(svg);
					}
					break;
//
				case 'retreat':
				case 'retreatE':
					{
						dojo.query(`.ERAship[color=${this.color}][location='${state.args.location}']`, 'ERAboard').addClass('ERAselectable');
//
						const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
						dojo.setStyle(svg, 'position', 'absolute');
						dojo.setStyle(svg, 'left', '0px');
						dojo.setStyle(svg, 'top', '0px');
						dojo.setStyle(svg, 'z-index', '1');
						dojo.setStyle(svg, 'pointer-events', 'all');
						svg.setAttribute("width", 10000);
						svg.setAttribute("height", 10000);
						svg.id = 'ERAcombatChoice';
//
						for (let location of state.args.retreat)
						{
							dojo.query(`[location='${location}']`, 'ERAboard').addClass('ERAselectable');
							svg.appendChild(this.board.drawHexagon(this.board.hexagons[location], "#" + state.args.active + 'C0'));
						}
//
						this.board.board.appendChild(svg);
					}
					break;
//
				case 'battleLoss':
					{
						const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
						dojo.setStyle(svg, 'position', 'absolute');
						dojo.setStyle(svg, 'left', '0px');
						dojo.setStyle(svg, 'top', '0px');
						dojo.setStyle(svg, 'z-index', '1');
						dojo.setStyle(svg, 'pointer-events', 'all');
						svg.setAttribute("width", 10000);
						svg.setAttribute("height", 10000);
						svg.id = 'ERAstars';
//
						dojo.query(`[location='${state.args.location}']`, 'ERAboard').addClass('ERAselectable');
						svg.appendChild(this.board.drawHexagon(this.board.hexagons[state.args.location], "#" + state.args.active + 'C0'));
//
						this.board.board.appendChild(svg);
					}
					break;
//
				case 'selectCounters':
					break;
//
				case 'blockMovement':
					if ('_private' in state.args)
					{
						const location = state.args._private.location;
//
						const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
						dojo.setStyle(svg, 'position', 'absolute');
						dojo.setStyle(svg, 'left', '0px');
						dojo.setStyle(svg, 'top', '0px');
						dojo.setStyle(svg, 'z-index', '1');
						dojo.setStyle(svg, 'pointer-events', 'all');
						svg.setAttribute("width", 10000);
						svg.setAttribute("height", 10000);
						svg.id = 'ERAstars';
//
						dojo.query(`[location='${state.args._private.from}']`, 'ERAboard').addClass('ERAselectable');
						svg.appendChild(this.board.drawHexagon(this.board.hexagons[state.args._private.from], "#" + state.args.active + 'C0'));
//
						const SVGpath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
						let path = 'M' + this.board.hexagons[state.args._private.from].x + ' ' + this.board.hexagons[state.args._private.from].y;
						path += 'L' + this.board.hexagons[state.args._private.to].x + ' ' + this.board.hexagons[state.args._private.to].y;
						SVGpath.setAttribute('stroke', '#ffffffc0');
						SVGpath.setAttribute('fill', 'none');
						SVGpath.setAttribute('d', path);
						SVGpath.setAttribute('stroke-width', '2');
						svg.appendChild(SVGpath);
//
						dojo.query(`[location='${state.args._private.to}']`, 'ERAboard').addClass('ERAselectable');
						svg.appendChild(this.board.drawHexagon(this.board.hexagons[state.args._private.to], "#" + state.args._private.color + 'C0'));
//
						this.board.board.appendChild(svg);
					}
					break;
//
				case 'blockAction':
				case 'blockHomeStarEvacuation':
					if ('_private' in state.args)
					{
						for (let location of Object.values(state.args._private.locations))
						{
							const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
							dojo.setStyle(svg, 'position', 'absolute');
							dojo.setStyle(svg, 'left', '0px');
							dojo.setStyle(svg, 'top', '0px');
							dojo.setStyle(svg, 'z-index', '1');
							dojo.setStyle(svg, 'pointer-events', 'all');
							svg.setAttribute("width", 10000);
							svg.setAttribute("height", 10000);
							svg.id = 'ERAstars';
//
							dojo.query(`[location='${location}']`, 'ERAboard').addClass('ERAselectable');
							const path = svg.appendChild(this.board.drawHexagon(this.board.hexagons[location], "#" + state.args.active + 'C0'));
//							path.setAttribute('class', 'ERAselected');
//
							this.board.board.appendChild(svg);
						}
					}
					break;
//
				case 'resolveGrowthActions':
					dojo.query('.ERAprovisional,.ERAprovisionalBonus').remove().forEach((node) => this.counters.arrange(dojo.getAttr(node, 'location')));
					dojo.query('.ERAdisabled').removeClass('ERAdisabled');
					break;
//
				case 'researchPlus':
//
					if (this.isCurrentPlayerActive())
					{
						if ('Military' in state.args._private) for (const color in state.args._private.Military) dojo.query(`.ERAavailableGrowthAction>.ERAcounter`, `ERAcounters-${color}`).addClass('ERAselectable');
					}
//
					break;
//
				case 'removePopulation':
				case 'teleportPopulation':
					if (this.isCurrentPlayerActive())
					{
						const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
						dojo.setStyle(svg, 'position', 'absolute');
						dojo.setStyle(svg, 'left', '0px');
						dojo.setStyle(svg, 'top', '0px');
						dojo.setStyle(svg, 'z-index', '1');
						dojo.setStyle(svg, 'pointer-events', 'all');
						svg.setAttribute("width", 10000);
						svg.setAttribute("height", 10000);
						svg.id = 'ERAstars';
//
						dojo.query(`#ERAboard>.ERAhomeStar[color='${this.color}']`).addClass('ERAselectable').addClass('ERAselected');
						for (let location of state.args._private.populations)
						{
							dojo.query(`#ERAboard>.ERAship[location='${location}']`).style('opacity', '50%');
							dojo.query(`#ERAboard>.ERAcounter-populationDisc.ERAcounter-${this.color}[location='${location}']`).addClass('ERAselectable').addClass('ERAselected');
							svg.appendChild(this.board.drawHexagon(this.board.hexagons[location], "#" + state.args.active + 'C0'));
						}
						this.board.board.appendChild(svg);
					}
					break;
//
				case 'growPopulation':
				case 'growPopulation+':
					if (this.isCurrentPlayerActive())
					{
						const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
						dojo.setStyle(svg, 'position', 'absolute');
						dojo.setStyle(svg, 'left', '0px');
						dojo.setStyle(svg, 'top', '0px');
						dojo.setStyle(svg, 'z-index', '1');
						dojo.setStyle(svg, 'pointer-events', 'all');
						svg.setAttribute("width", 10000);
						svg.setAttribute("height", 10000);
						svg.id = 'ERAstars';
//
						for (let [location, {population: population, growthLimit: growthLimit}] of Object.entries(state.args._private.growPopulation))
						{
							if (population < growthLimit)
							{
								dojo.query(`#ERAboard>.ERAship[location='${location}']`).style('opacity', '50%');
								dojo.query(`#ERAboard .ERAcounter-populationDisc.ERAcounter-${this.color}[location='${location}']`).addClass('ERAselectable').addClass('ERAselected');
								svg.appendChild(this.board.drawHexagon(this.board.hexagons[location], "#" + state.args.active + 'C0'));
							}
						}
						this.board.board.appendChild(svg);
					}
//
					break;
//
				case 'bonusPopulation':
					if (this.isCurrentPlayerActive())
					{
						const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
						dojo.setStyle(svg, 'position', 'absolute');
						dojo.setStyle(svg, 'left', '0px');
						dojo.setStyle(svg, 'top', '0px');
						dojo.setStyle(svg, 'z-index', '1');
						dojo.setStyle(svg, 'pointer-events', 'all');
						svg.setAttribute("width", 10000);
						svg.setAttribute("height", 10000);
						svg.id = 'ERAstars';
//
						dojo.query(`#ERAboard>.ERAhomeStar[color='${this.color}']`).addClass('ERAselectable').addClass('ERAselected');
						for (let [location, {population: population, growthLimit: growthLimit}] of Object.entries(state.args._private.growPopulation))
						{
							dojo.query(`#ERAboard>.ERAship[location='${location}']`).style('opacity', '50%');
							dojo.query(`#ERAboard>.ERAcounter-populationDisc.ERAcounter-${this.color}[location='${location}']`).addClass('ERAselectable').addClass('ERAselected');
							svg.appendChild(this.board.drawHexagon(this.board.hexagons[location], "#" + state.args.active + 'C0'));
						}
						this.board.board.appendChild(svg);
					}
					break;
//
				case 'buildShips':
				case 'buriedShips':
				case 'emergencyReserve':
					if ('_private' in state.args)
					{
						dojo.query('.ERAprovisional').remove().forEach((node) => this.counters.arrange(dojo.getAttr(node, 'location')));
//
						const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
						dojo.setStyle(svg, 'position', 'absolute');
						dojo.setStyle(svg, 'left', '0px');
						dojo.setStyle(svg, 'top', '0px');
						dojo.setStyle(svg, 'z-index', '1');
						dojo.setStyle(svg, 'pointer-events', 'all');
						svg.setAttribute("width", 10000);
						svg.setAttribute("height", 10000);
						svg.id = 'ERAstars';
//
						for (let location of state.args._private.stars)
						{
							dojo.query(`[location='${location}']`, 'ERAboard').addClass('ERAselectable');
							const path = svg.appendChild(this.board.drawHexagon(this.board.hexagons[location], "#" + state.args.active + 'C0'));
							if (location === this.location) path.setAttribute('class', 'ERAselected');
						}
//
						this.board.board.appendChild(svg);
					}
					break;
//
				case 'gainStar':
				case 'gainStar+':
					if ('_private' in state.args)
					{
						const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
						dojo.setStyle(svg, 'position', 'absolute');
						dojo.setStyle(svg, 'left', '0px');
						dojo.setStyle(svg, 'top', '0px');
						dojo.setStyle(svg, 'z-index', '1');
						dojo.setStyle(svg, 'pointer-events', 'all');
						svg.setAttribute("width", 10000);
						svg.setAttribute("height", 10000);
						svg.id = 'ERAstars';
//
						dojo.query(`.ERAcounter-peace`, `ERAstatus-${this.color}`).addClass('ERAselectable');
						for (let location of Object.keys(state.args._private.gainStar))
						{
							if (stateName === 'gainStar+' && location[0] !== '0') continue;
							dojo.query(`#ERAboard>.ERAship[location='${location}']`).addClass('ERAselectable').style('opacity', '50%');
							dojo.query(`.ERAcounter[location='${location}']`, 'ERAboard').addClass('ERAselectable ERAselected');
							svg.appendChild(this.board.drawHexagon(this.board.hexagons[location], "#" + state.args.active + 'C0'));
						}
						this.board.board.appendChild(svg);
					}
					break;
//
				case 'tradingPhase':
					{
						if (/*this.isCurrentPlayerActive()*/ 'trade' in state.args._private)
						{
							dojo.place(this.format_block('ERAchoice', {}), 'game_play_area');
//
							const from = state.args._private.color;
							for (let to in state.args._private.inContact)
							{
								const _container = dojo.place(`<div style='display: flex;flex-direction: column;flex: 1 1 auto;max-width: 500px;'></div>`, 'ERAchoice');
								const container = dojo.place(`<div style='display: flex;justify-content: space-evenly;margin: 1%;padding: 5%; border-radius: 5%;' class='ERAtrade' id='ERAtrade-${from}-${to}'></div>`, _container);
								dojo.style(container, 'background', '#' + to + '80');
//
								const fromColor = dojo.place(`<div style='display: flex;flex-direction: column;flex: 1 1 auto;align-items: center;' class='ERAtrade' id='ERAtradeFrom-${from}-${to}'></div>`, container);
								dojo.place(`<div style='color:white;margin-bottom: 10px;'>${_('You are teaching')}</div>`, fromColor);
								for (let [technology, level] of Object.entries(state.args._private.inContact[to]))
								{
									const node = dojo.place(this.format_block('ERAcounter', {id: technology, color: to, type: 'technology', location: ''}), fromColor);
									dojo.toggleClass(node, 'ERAhide', level >= state.args._private[technology]);
									dojo.toggleClass(node, 'ERAselectable', !dojo.hasClass(node, 'ERAdisabled'));
									dojo.toggleClass(node, 'ERAselected', to in state.args._private.trade && from in state.args._private.trade[to] && state.args._private.trade[to][from].technology === technology);
//
									if (to === state.args.automa) dojo.connect(node, 'click', () => {
											dojo.query('.ERAcounter.ERAcounter-technology', `ERAtradeFrom-${this.color}-${to}`).removeClass('ERAselected');
											dojo.addClass(node, 'ERAselected');
										});
								}
//
								const toColor = dojo.place(`<div style='display: flex;flex-direction: column;flex: 1 1 auto;align-items: center;' class='ERAtrade' id='ERAtradeTo-${from}-${to}'></div>`, container);
								dojo.place(`<div style='color:white;margin-bottom: 10px;'>${_('You are getting')}</div>`, toColor);
								for (let [technology, level] of Object.entries(state.args._private.inContact[to]))
								{
									const node = dojo.place(this.format_block('ERAcounter', {id: technology, color: to, type: 'technology', location: ''}), toColor);
									dojo.toggleClass(node, 'ERAhide', level <= state.args._private[technology]);
									dojo.toggleClass(node, 'ERAselectable', !dojo.hasClass(node, 'ERAdisabled'));
									dojo.toggleClass(node, 'ERAselected', from in state.args._private.trade && to in state.args._private.trade[from] && state.args._private.trade[from][to].technology === technology);
//
									dojo.connect(node, 'click', () => {
										if (to === state.args.automa) {
											const nodes = dojo.query('.ERAcounter.ERAcounter-technology.ERAselected', `ERAtradeFrom-${this.color}-${to}`);
											if (nodes.length !== 1) this.showMessage(_('You must choose what to teach first'), 'error');
											else this.action('trade', {from: from, to: to, technology: technology, toTeach: nodes[0].getAttribute('counter')});
										}
										else this.action('trade', {from: from, to: to, technology: technology});
									});
								}
								const node = dojo.place('<div style="display: flex;justify-content: space-between;"></div>', _container);
								const refuse = dojo.place(`<div class='bgabutton'>${_('Refuse trade')}</div>`, node);
								dojo.style(refuse, 'background', '#' + to + '80');
								dojo.style(refuse, 'pointer-events', 'all');
								dojo.connect(refuse, 'click', () => this.action('trade', {from: from, to: to, technology: 'refuse'}));
//								const accept = dojo.place(`<div class='bgabutton'>${_('Accept trade')}</div>`, node);
//								dojo.style(accept, 'background', '#' + to + '80');
//								dojo.style(accept, 'pointer-events', 'all');
//								if (from in state.args._private.trade && to in state.args._private.trade[from] && state.args._private.trade[from][to].pending) dojo.addClass(accept, 'ERAdisabled', );
//								if (to in state.args._private.trade && from in state.args._private.trade[to] && state.args._private.trade[to][from].pending) dojo.addClass(accept, 'ERAdisabled', );
//								dojo.connect(accept, 'click', () => this.action('trade', {from: from, to: to, technology: 'accept'}))
							}
//
							let html = '';
							for (let to in state.args._private.trade[from])
							{
								if (state.args._private.trade[from][to].pending)
								{
									html += `<span style='color:#${from};'>${_('You')}</span> ` + _("are trading with") + ` <span style='color:#${to};'>${this.gamedatas.players[this.gamedatas.factions[to].player_id].name}</span>`;
									html += '<div style="text-align:center;">';
									dojo.query('.ERAcounter-technology.ERAselected', `ERAtradeFrom-${from}-${to}`).forEach((node) => {
										const technology = dojo.getAttr(node, 'counter');
										html += '<div style="display:inline-block;vertical-align:middle;">' + this.format_block('ERAcounter', {id: technology, color: from, type: 'technology', location: ''}) + '</div>';
									});
									html += '<span style="vertical-align:middle;font-size:48pt;">⇄</span>';
									dojo.query('.ERAcounter-technology.ERAselected', `ERAtradeTo-${from}-${to}`).forEach((node) => {
										const technology = dojo.getAttr(node, 'counter');
										html += '<div style="display:inline-block;vertical-align:middle;">' + this.format_block('ERAcounter', {id: technology, color: to, type: 'technology', location: ''}) + '</div>';
									});
									html += '</div>';
									html += '<div style="display: flex;justify-content: space-between;">';
									html += `<div id="ERAacceptButton-${to}" class='bgabutton bgabutton_blue' onclick="gameui.action('trade', {from: '${from}', to: '${to}', technology: 'confirm'})">${_('Accept trade')}</div>`;
									html += `<div id="ERArefuseButton-${to}" class='bgabutton bgabutton_blue' onclick="gameui.action('trade', {from: '${from}', to: '${to}', technology: 'refuse'})">${_('Refuse trade')}</div>`;
									html += '</div>';
									html += '<HL>';
								}
							}
							if (html)
							{
								this.ERAtrade = new ebg.popindialog();
								this.ERAtrade.create('ERAtrade');
								this.ERAtrade.setTitle(_('You must accept or refuse trading'));
								this.ERAtrade.setContent(html);
								this.ERAtrade.hideCloseIcon();
								this.ERAtrade.show();
								dojo.style('popin_ERAtrade_underlay', 'visibility', 'hidden');
							}
						}
					}
					break;
//
				case 'homeStarEvacuation':
//
					if (this.isCurrentPlayerActive())
					{
						this.board.centerMap(state.args._private.homeStar);
//
						const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
						dojo.setStyle(svg, 'position', 'absolute');
						dojo.setStyle(svg, 'left', '0px');
						dojo.setStyle(svg, 'top', '0px');
						dojo.setStyle(svg, 'z-index', '1');
						dojo.setStyle(svg, 'pointer-events', 'all');
						svg.setAttribute("width", 10000);
						svg.setAttribute("height", 10000);
						svg.id = 'ERAstars';
//
						svg.appendChild(this.board.drawHexagon(this.board.hexagons[state.args._private.homeStar], '#000000C0'));
						for (let location of state.args._private.evacuate)
						{
							dojo.query(`#ERAboard>.ERAcounter-populationDisc.ERAcounter-${this.color}[location='${location}']`).addClass('ERAselectable').addClass('ERAselected');
							svg.appendChild(this.board.drawHexagon(this.board.hexagons[location], "#" + state.args._private.color + 'C0'));
						}
//
						this.board.board.appendChild(svg);
					}
					break;
//
				case 'planetaryDeathRay':
//
					if ('_private' in state.args)
					{
						const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
						dojo.setStyle(svg, 'position', 'absolute');
						dojo.setStyle(svg, 'left', '0px');
						dojo.setStyle(svg, 'top', '0px');
						dojo.setStyle(svg, 'z-index', '1');
						dojo.setStyle(svg, 'pointer-events', 'all');
						svg.setAttribute("width", 10000);
						svg.setAttribute("height", 10000);
						svg.id = 'ERAstars';
//
						for (let location of state.args._private.planetaryDeathRayTargets)
						{
							dojo.query('.ERAcounter-war:not(.ERAhide)', `ERAstatus-${this.color}`).forEach((node) => {
								const otherColor = dojo.getAttr(node, 'on');
								dojo.query(`.ERAship[color='${otherColor}'][location='${location}']`, 'ERAboard').addClass('ERAselectable ERAselected');
								dojo.query(`.ERAcounter-populationDisc.ERAcounter-${otherColor}[location='${location}']`, 'ERAboard').addClass('ERAselectable ERAselected');
							});
							svg.appendChild(this.board.drawHexagon(this.board.hexagons[location], "#" + state.args.active + 'C0'));
						}
						this.board.board.appendChild(svg);
					}
//
				case 'oneTimeEffect':
//
					if (this.isCurrentPlayerActive())
					{
						if ('exploratory' in state.args._private) {
							dojo.place(`<span style='font-size:small;'><BR>${_('You may inspect the unplayed domination cards of another player (in a game with 5+ players, you may even do this with 2 players)')}</span>`, 'generalactions');
							dojo.query(`.ERAdominationCard[domination='back']`).addClass('ERAselectable ERAselected');
						}

					}
//
					break;
			}
		},
		onLeavingState: function (stateName)
		{
			console.log('Leaving state: ' + stateName);
//
			if (this.factions.dialog) this.factions.dialog.destroy();
//
			dojo.destroy('ERAchoice');
			dojo.destroy('ERApath');
			dojo.destroy('ERAcombatChoice');
			dojo.destroy('ERAstars');
//
			dojo.query('#ERAfleets').addClass('ERAhide');
			dojo.query('.ERAselected').removeClass('ERAselected');
			dojo.query('.ERAselectable').removeClass('ERAselectable');
			dojo.query('.ERAdisabled').removeClass('ERAdisabled');
			dojo.query(`#ERAboard>.ERAship`).style('opacity', '');
//
			dojo.query('#popin_trade').remove();
			dojo.query('#popin_ERAtrade').remove();
//
			dojo.query('svg', 'ERAboard').remove();
		},
		onUpdateActionButtons: function (stateName, args)
		{
			console.log('onUpdateActionButtons: ' + stateName, args);
//
			if (this.isCurrentPlayerActive())
			{
				if (this.on_client_state) this.addActionButton('ERAcancelButton', _('Cancel'), () => this.restoreServerGameState());
				if (this.gamedatas.gamestate.possibleactions.includes('declareWar')) dojo.query(`.ERAcounter-peace[color='${this.color}']`, 'player_boards').addClass('ERAselectable');
				if (this.gamedatas.gamestate.possibleactions.includes('declarePeace')) dojo.query(`.ERAcounter-war[color='${this.color}']`, 'player_boards').addClass('ERAselectable');
//
				if (this.gamedatas.gamestate.possibleactions.includes('remoteViewing'))
				{
					if (!this.on_client_state)
					{
						this.addActionButton('ERAviewButton', '<span class="fa fa-eye fa-spin"></span> ×' + (args._private.view < 0 ? '∞' : args._private.view), () =>
						{
							if (stateName === 'remoteViewing') return this.restoreServerGameState();
							args.ancientPyramids = false;
							if (args._private.view) this.setClientState('remoteViewing', {descriptionmyturn: _('${you} may secretly look at one “hidden thing”')});
						});
						dojo.setAttr('ERAviewButton', 'title', _('Remote Viewing is the psychic ability to tap into the Universal Mind to see any event anywhere in space and time'));
						if (args._private.view === 0) dojo.style('ERAviewButton', 'filter', 'grayscale(1)');
						if (args._private.ancientPyramids)
						{
							const node = dojo.place(`<div class='ERAcounter ERAcounter-relic ERAcounter-0 action-button' style='display:inline-block;vertical-align:middle;'></div>`, 'generalactions');
							dojo.connect(node, 'click', () => {
								if (stateName === 'remoteViewing') return this.restoreServerGameState();
								args.ancientPyramids = true;
								if (args._private.ancientPyramids) this.setClientState('remoteViewing', {descriptionmyturn: _('${you} may secretly look at one “hidden thing” using Ancient Pyramids')});
							});
						}
					}
				}
				if (this.gamedatas.gamestate.possibleactions.includes('planetaryDeathRay'))
				{
					if (!this.on_client_state)
					{
						if (args._private.planetaryDeathRay)
						{
							const node = dojo.place(`<div class='ERAcounter ERAcounter-relic ERAcounter-7 action-button' style='display:inline-block;vertical-align:middle;'></div>`, 'generalactions');
							dojo.connect(node, 'click', () => {
								if (stateName === 'planetaryDeathRay') return this.restoreServerGameState();
								if (args._private.planetaryDeathRay !== 0) this.setClientState('planetaryDeathRay', {descriptionmyturn: _('${you} may destroy 1 ship or population disc')});
							});
						}
					}
				}
//
				if ('_private' in args && 'fleets' in args._private)
				{
					dojo.empty('ERAfleets');
					for (let [fleet, {location: location, ships: ships}] of Object.entries(args._private.fleets))
					{
						const _fleetNode = dojo.place(this.format_block('ERAfleet', {fleet: fleet, location: location, ships: ships}), 'ERAfleets');
//
						dojo.place(`<div class='ERAfleetAction' style="color:white;"></div>`, _fleetNode);
						const fleetNode = dojo.place(this.format_block('ERAship', {id: fleet, color: this.color, ship: ships, location: location}), _fleetNode);
						dojo.setAttr(fleetNode, 'fleet', fleet);
						dojo.setAttr(fleetNode, 'ships', ships);
//
						const shipsNode = dojo.place(`<div class='ERAships' style='display:relative;width:50px;height:0px'></div>`, _fleetNode);
						for (let index = 0; index < ships; index++)
						{
							let node = dojo.place(this.format_block('ERAship', {id: fleet, color: this.color, location: location}), shipsNode);
							dojo.addClass(node, 'ERAselectable');
							dojo.connect(node, 'click', (event) => {
								dojo.stopEvent(event);
								if (event.detail === 1) dojo.toggleClass(node, 'ERAselected');
								if (event.detail === 2) dojo.query(`#ERAfleets>.ERAfleet[fleet='${fleet}'] .ERAship:not([fleet]).ERAselectable`).toggleClass('ERAselected', dojo.hasClass(node, 'ERAselected'));
							});
						}
//
						dojo.connect(fleetNode, 'click', (event) => {
							dojo.stopEvent(event);
//
							const fleetNode = event.currentTarget;
							const fleet = dojo.getAttr(fleetNode, 'fleet');
							let ships = +dojo.getAttr(fleetNode, 'ships');
//
							if (stateName === 'buriedShips' || stateName === 'buildShips' || stateName === 'emergencyReserve')
							{
								const shipNode = $('ERAbuildShip');
								if (shipNode)
								{
									dojo.destroy(shipNode);
//
									if (ships === 0)
									{
										$('ERAfleets').querySelector(`.ERAfleet[fleet='${fleet}']`).setAttribute('location', this.location);
//
										const node = this.ships.place({id: '', color: this.color, fleet: fleet, location: this.location});
										dojo.addClass(node, 'ERAselectable ERAprovisional');
									}
									dojo.setAttr(fleetNode, 'ships', ++ships);
//
									let node = dojo.place(this.format_block('ERAship', {id: fleet, color: this.color, location: fleet}), $('ERAfleets').querySelector(`.ERAfleet[fleet='${fleet}'] .ERAships`));
									dojo.addClass(node, 'ERAselectable ERAprovisional');
									dojo.connect(node, 'click', (event) => {
										dojo.stopEvent(event);
//
										while (node)
										{
											next = node.nextSibling;
											dojo.destroy(node);
											const shipNode = dojo.place(`<div id='ERAbuildShip' style='width:50px;height:50px;transform:scale(20%);transform-origin:left top;margin-right:-30px'></div>`, 'ERAbuildShips');
											dojo.place(this.format_block('ERAship', {id: 'buildShip', color: this.color, location: ''}), shipNode);
											node = next;
										}
										dojo.setAttr(fleetNode, 'ships', --ships);
										if (ships === 0) {
											dojo.query(`.ERAship[fleet='${fleet}'].ERAprovisional`, 'ERAboard').remove();
											$('ERAfleets').querySelector(`.ERAfleet[fleet='${fleet}']`).setAttribute('location', 'stock');
										}
									});
								}
								return;
							}
//
							const shipsToFleet = dojo.query(`#ERAboard .ERAship.ERAselected:not([fleet])`).reduce((L, node) => [...L, +node.getAttribute('ship')], []);
							if (shipsToFleet.length)
							{
//								dojo.empty('ERAfleets');
								return this.action('shipsToFleet', {color: this.color, fleet: fleet, ships: JSON.stringify(shipsToFleet)});
							}
							const fleetToShips = dojo.query(`#ERAfleets .ERAship.ERAselected[ship='${fleet}']:not([fleet])`).reduce((L, node) => [...L, +node.getAttribute('ship')], []);
							if (fleetToShips.length)
							{
//								dojo.empty('ERAfleets');
								return this.action('fleetToShips', {color: this.color, fleet: fleet, ships: fleetToShips.length});
							}
							const fleetToFleet = dojo.query(`#ERAfleets .ERAship.ERAselected:not([ship='${fleet}']):not([fleet])`);
							if (fleetToFleet.length)
							{
//								dojo.empty('ERAfleets');
								return this.action('fleetToFleet', {color: this.color, from: dojo.getAttr(fleetToFleet[0], 'ship'), to: fleet, ships: fleetToFleet.length});
							}
							const fleets = dojo.query(`#ERAboard .ERAship.ERAselected[fleet]`).reduce((L, node) => [...L, node.getAttribute('fleet')], [fleet]);
							dojo.empty('ERAfleets');
							this.action('swapFleets', {color: this.color, fleets: JSON.stringify(fleets)});
						}
						);
					}
				}
//
				switch (stateName)
				{
					case 'starPeopleChoice':
//
						this.addActionButton('ERAconfirmButton', _('Use selected Star People'), () => {
							const starPeople = dojo.getAttr(document.querySelector('#ERAchoice .ERAstarPeople.ERAselected'), 'starPeople');
							this.action('starPeopleChoice', {color: args._private.color, starPeople: starPeople}, () => {
								if (this.gamedatas.gamestate.name === 'starPeopleChoice')
								{
									this.last_server_state.args._private.starPeople = [starPeople];
									this.restoreServerGameState();
								}
							});
						}
						);
						dojo.addClass('ERAconfirmButton', 'disabled');
						break;
//
					case 'alignmentChoice':
//
						this.addActionButton('ERAconfirmButton', _('Use selected Alignment'), () => {
							const alignment = dojo.hasClass(document.querySelector('#ERAchoice .ERAstarPeople'), 'ERA-STS');
							this.action('alignmentChoice', {color: args._private.color, alignment: alignment}, () => {
								if (this.gamedatas.gamestate.name === 'alignmentChoice')
								{
									this.last_server_state.args._private.alignment = alignment;
									this.restoreServerGameState();
								}
							});
						}
						);
						break;
//
					case 'domination':
//
						if (!args.lastChance)
						{
							this.addActionButton('ERAPassButton', _('Pass'), () => {
								this.action('null', {color: this.color});
								if (this.factions.dialog) this.factions.dialog.destroy();
							});
						}
//
						break;
//
					case 'dominationCardExchange':
//
						for (let card of Object.values(args._private.hand))
						{
							this.addActionButton('ERAexchnageButton-' + card.id, dojo.string.substitute(_('Exchange ${domination}'), {domination: this.DOMINATIONS[card.type].title}), () => this.action('dominationCardExchange', {color: this.color, id: card.id}), null, false, 'red');
							this.addActionButton('ERAcancelButton', _('No Exchange'), () => this.action('dominationCardExchange', {color: this.color, id: 0}), null, false, 'red');
						}
						break;
//
					case 'advancedFleetTactics':
//
						dojo.empty('ERAfleets');
						for (let [fleet, {location: location, ships: ships}] of Object.entries(args._private.fleets))
						{
							const _fleetNode = dojo.place(this.format_block('ERAfleet', {fleet: fleet, location: location, ships: ships}), 'ERAfleets');
//
							dojo.place(`<div class='ERAfleetAction' style="color:white;"></div>`, _fleetNode);
							const fleetNode = dojo.place(this.format_block('ERAship', {id: fleet, color: args._private.color, ship: ships, location: location}), _fleetNode);
							dojo.setAttr(fleetNode, 'fleet', fleet);
							dojo.toggleClass(_fleetNode, 'ERAhide', args._private.advancedFleetTactics[fleet]);
							dojo.toggleClass(fleetNode, 'ERAselectable', !args._private.advancedFleetTactics[fleet]);
//
							const shipsNode = dojo.place(`<div style='display:relative;width:50px;height:0px'></div>`, _fleetNode);
							for (let index = 0; index < ships; index++) dojo.place(this.format_block('ERAship', {id: fleet, color: args._private.color, location: location}), shipsNode);
//
							dojo.connect(fleetNode, 'click', (event) => {
								dojo.stopEvent(event);
								if (dojo.hasClass(event.currentTarget, 'ERAselected')) {
									dojo.query('.ERAcounter-tactics', 'generalactions').addClass('ERAdisabled');
									dojo.removeClass(event.currentTarget, 'ERAselected');
								}
								else
								{
									dojo.query('.ERAcounter-tactics', 'generalactions').removeClass('ERAdisabled');
									dojo.query('.ERAship[fleet].ERAselectable', 'ERAfleets').removeClass('ERAselected');
									dojo.addClass(event.currentTarget, 'ERAselected');
								}
							}
							);
						}
						dojo.removeClass('ERAfleets', 'ERAhide');
//
						const node2x = dojo.place(this.format_block('ERAcounter', {id: '2x', color: args._private.color, type: 'tactics', location: ''}), 'generalactions');
						dojo.setAttr(node2x, 'tactics', '2x');
						const nodeDP = dojo.place(this.format_block('ERAcounter', {id: 'DP', color: args._private.color, type: 'tactics', location: ''}), 'generalactions');
						dojo.setAttr(nodeDP, 'tactics', 'DP');
//
						dojo.query('.ERAcounter-tactics', 'generalactions').addClass('ERAselectable ERAdisabled').connect('click', (event) => {
							const fleets = dojo.query('.ERAship[fleet].ERAselected', 'ERAfleets');
							if (fleets.length === 1) this.action('advancedFleetTactics', {color: args._private.color, fleet: dojo.getAttr(fleets[0], 'fleet'), tactics: event.currentTarget.getAttribute('tactics')});
						});
						break;
//
					case 'fleets':
//
						this.board.centerMap(this.gamedatas.factions[this.players[this.player_id]].homeStar + ':+0+0+0');
//
						if (args.undo) this.addActionButton('ERAundoButton', _('Undo'), () => this.action('undo', {color: this.color}));
//
						this.addActionButton('ERAdoneButton', _('Go to Movement step'), () => {
							const node = $('ERAdoneButton');
							if (node.count)
							{
								delete node.count;
								return this.restoreServerGameState();
							}
							dojo.query('#generalcounters .bgabutton:not(#ERAdoneButton)').remove();
							node.count = 1;
							const timer = window.setInterval(() => {
								node.count -= 1;
								node.innerHTML = _('Cancel') + ` (${(+node.count / 10).toFixed(1)}s)`;
								if (node.count < 0)
								{
									window.clearInterval(timer);
									return this.action('done', {color: this.color});
								}
							}, 100);
						}, null, false, 'red');
						break;
//
					case 'movement':
//
						if (args.undo) this.addActionButton('ERAundoButton', _('Undo'), () => this.action('undo', {color: this.color}));
//
						this.addActionButton('ERAscoutButton', _('Scout'), () => {
							const ships = dojo.query(`#ERAboard .ERAship.ERAselected`).reduce((L, node) => [...L, +node.getAttribute('ship')], []);
							if ($('ERApath'))
							{
								this.confirmationDialog(_('Scouting will end movement of selected ships'), () =>
								{
									this.action('scout', {color: this.color, ships: JSON.stringify(ships)});
								});
							}
							else this.action('scout', {color: this.color, ships: JSON.stringify(ships)});
						});
						dojo.addClass('ERAscoutButton', 'disabled');
//
						this.addActionButton('ERApassButton', _('End turn'), () => {
							const node = $('ERApassButton');
							if (node.count)
							{
								delete node.count;
								return this.restoreServerGameState();
							}
							dojo.query('#generalcounters .bgabutton:not(#ERApassButton)').remove();
							node.count = 1;
							const timer = window.setInterval(() => {
								node.count -= 1;
								node.innerHTML = _('Cancel') + ` (${(+node.count / 10).toFixed(1)}s)`;
								if (node.count < 0)
								{
									window.clearInterval(timer);
									return this.action('done', {color: this.color});
								}
							}, 100);
						}, null, false, 'red');
						break;
//
					case 'retreat':
					case 'retreatE':
//
						if (!args.winner) this.addActionButton('ERAnoRetreat', _('No retreat'), () => this.action('retreat', {color: args.active, location: '""'}));
						break;
//
					case 'battleLoss':
//
						this.addActionButton('ERAreset', _('Reset'), () => this.restoreServerGameState());
						this.addActionButton('ERAdone', _('Done'), () => {
							let ships = {winner: [], losers: []};
							dojo.query('.ERAship:not([fleet]).ERAselected', 'ERAwinner').forEach((node) => ships.winner.push([node.getAttribute('color'),
									node.getAttribute('location')]));
							dojo.query('.ERAship:not([fleet]).ERAselected', 'ERAlosers').forEach((node) => ships.losers.push([node.getAttribute('color'),
									node.getAttribute('location')]));
							this.action('battleLoss', {color: this.color, ships: JSON.stringify(ships)});
						});
//
						if (args.totalVictory) dojo.place(`<span style='font-size:small;'><BR>${_('No ships are destroyed from the winning side')}</span>`, 'generalactions');
						else dojo.place(`<span style='font-size:small;'><BR><span id='ERAtoDestroy'></span></span>`, 'generalactions');
//
						const node = dojo.place(this.format_block('ERAchoice', {}), 'game_play_area');
						dojo.style(node, 'pointer-events', 'all');
//
						for (let side of ['winner', 'losers'])
						{
							const sideNode = dojo.place(`<div id='ERA${side}' style='flex:1 1 50%;display:flex;flex-direction:column;background:#000000C0;margin:10px;padding:10px;border-radius:25px'></div>`, 'ERAchoice');
							dojo.place(`<div style='margin:5% 0% 5% 5%;color:white;'><span style='font-weight:bold;'>${{winner: _('Winner side'), losers: _('Loser side')}[side]}&nbsp:&nbsp</span><span id='ERA${side}Lose'></span>&nbsp${_('ship(s) destroyed')}</div>`, sideNode);
//
							for (let color in args[side])
							{
								for (let [fleet, ships] of Object.entries(args[side][color]))
								{
									if (ships > 0)
									{
										const fleetNode = dojo.place(this.format_block('ERAfleetH', {color: color, fleet: fleet, ships: ships}), sideNode);
//
										if (fleet !== 'ships')
										{
											const node = dojo.place(this.format_block('ERAship', {id: color + '-' + fleet, color: color, ship: ships, location: ''}), `ERAfleet-${color}-${fleet}`);
											dojo.setAttr(node, 'fleet', fleet);
											dojo.style(node, 'position', 'relative');
											dojo.addClass(node, 'ERAselectable');
											if (side === 'winner' && args.totalVictory) dojo.addClass(node, 'ERAdisabled');
											else dojo.connect(node, 'click', (event) => {
													dojo.stopEvent(event);
													dojo.query(`.ERAship:not([fleet]).ERAselectable`, fleetNode).toggleClass('ERAselected', dojo.query(`.ERAship:not([fleet]).ERAselected`, fleetNode).length === 0);
													$(`ERAfleetSelector-${color}-${fleet}`).value = dojo.query(`.ERAship:not([fleet]).ERAselected`, fleetNode).length;
													this.battleLoss(args.totalVictory);
												});
											dojo.addClass(node, 'ERAselectable');
										}
//
										for (let index = 0; index < ships; index++)
										{
											const shipNode = dojo.place(`<div style='width:50px;height:50px;transform:scale(20%);transform-origin:left top;margin-right:-25px'></div>`, `ERAfleet-${color}-${fleet}`);
											const node = dojo.place(this.format_block('ERAship', {id: color + '-' + fleet + '-' + index, color: color, location: fleet}), shipNode);
											if (side === 'winner' && args.totalVictory) dojo.addClass(node, 'ERAdisabled');
											else dojo.connect(node, 'click', (event) => {
													dojo.stopEvent(event);
													let count = 0;
													dojo.query(`.ERAship:not([fleet]).ERAselectable`, fleetNode).forEach((node) => dojo.toggleClass(node, 'ERAselected', (count++) <= index));
													$(`ERAfleetSelector-${color}-${fleet}`).value = dojo.query(`.ERAship:not([fleet]).ERAselected`, fleetNode).length;
													this.battleLoss(args.totalVictory);
												});
											dojo.addClass(node, 'ERAselectable');
										}
//
										if (side === 'winner' && args.totalVictory) dojo.addClass($(`ERAfleetSelector-${color}-${fleet}`), 'ERAdisabled');
										else dojo.connect($(`ERAfleetSelector-${color}-${fleet}`), 'oninput', (event) => {
												let count = 0;
												dojo.query(`.ERAship:not([fleet]).ERAselectable`, fleetNode).forEach((node) => dojo.toggleClass(node, 'ERAselected', (count++) < event.target.value));
												this.battleLoss(args.totalVictory);
											});
										if (side === 'winner' && args.totalVictory) dojo.addClass($(`ERAfleetSelector-${color}-${fleet}-0`), 'ERAdisabled');
										else dojo.connect($(`ERAfleetSelector-${color}-${fleet}-0`), 'click', () => {
												dojo.query(`.ERAship:not([fleet]).ERAselectable`, fleetNode).removeClass('ERAselected');
												$(`ERAfleetSelector-${color}-${fleet}`).value = dojo.query(`.ERAship:not([fleet]).ERAselected`, fleetNode).length;
												this.battleLoss(args.totalVictory);
											});
										if (side === 'winner' && args.totalVictory) dojo.addClass($(`ERAfleetSelector-${color}-${fleet}-MAX`), 'ERAdisabled');
										else dojo.connect($(`ERAfleetSelector-${color}-${fleet}-MAX`), 'click', () => {
												dojo.query(`.ERAship:not([fleet]).ERAselectable`, fleetNode).addClass('ERAselected');
												$(`ERAfleetSelector-${color}-${fleet}`).value = dojo.query(`.ERAship:not([fleet]).ERAselected`, fleetNode).length;
												this.battleLoss(args.totalVictory);
											});
									}
								}
							}
						}
						this.battleLoss(args.totalVictory);
						break;
//
					case 'selectCounters':
//
						this.board.centerMap(this.gamedatas.factions[this.players[this.player_id]].homeStar + ':+0+0+0');
//
						this.addActionButton('ERAcancelButton', _('Clear selection'), () => this.restoreServerGameState());
						this.addActionButton('ERAselectButton', dojo.string.substitute(_('Select Growth Actions (${oval})'), {oval: args._private.oval}), () =>
						{
							const counters = dojo.query(`#ERAchoice .ERAselected`).reduce((L, node) => [...L, node.getAttribute('counter')], []);
							if (counters.includes('research') && counters.filter(action => ['Military', 'Spirituality', 'Propulsion', 'Robotics', 'Genetics'].includes(action)).length === 0)
							{
								this.confirmationDialog(_('Research growth action selected without technology counter(s)'), () => {
									this.action('selectCounters', {color: args._private.color, counters: JSON.stringify(counters)}, () => {
										if (this.gamedatas.gamestate.name === 'selectCounters')
										{
											const special = dojo.query(`#ERAchoice .ERAdisabled`).reduce((L, node) => [...L, node.getAttribute('counter')], []);
											this.last_server_state.args._private.counters = counters.concat(special);
											this.restoreServerGameState();
										}
									});
								});
							}
							else if (counters.filter(action => ['Military', 'Spirituality', 'Propulsion', 'Robotics', 'Genetics', 'changeTurnOrderUp', 'changeTurnOrderDown'
								].includes(action)).length < args._private.square && +this.gamedatas.GODMODE === 0)
							{
								this.confirmationDialog($('ERAwarning').innerHTML, () => {
									this.action('selectCounters', {color: args._private.color, counters: JSON.stringify(counters)}, () => {
										if (this.gamedatas.gamestate.name === 'selectCounters')
										{
											const special = dojo.query(`#ERAchoice .ERAdisabled`).reduce((L, node) => [...L, node.getAttribute('counter')], []);
											this.last_server_state.args._private.counters = counters.concat(special);
											this.restoreServerGameState();
										}
									});
								});
							}
							else
							{
								if ((counters.includes('research') && counters.includes('Robotics')) && (counters.includes('changeTurnOrderDown') || counters.includes('changeTurnOrderUp')) && dojo.hasClass($(`ERAtech-${this.color}-Robotics`).querySelector('.ERAtechnology').children[5], 'circleBlack'))
									return this.showMessage(_('You must select a second technology to use with Robotics+ effect'), 'error');
//
								this.action('selectCounters', {color: args._private.color, counters: JSON.stringify(counters)}, () => {
									if (this.gamedatas.gamestate.name === 'selectCounters')
									{
										const special = dojo.query(`#ERAchoice .ERAdisabled`).reduce((L, node) => [...L, node.getAttribute('counter')], []);
										this.last_server_state.args._private.counters = counters.concat(special);
										this.restoreServerGameState();
									}
								});
							}
						});
						dojo.toggleClass('ERAselectButton', 'disabled', !this.checkGrowthActions());
						if (args._private.additional > 0)
						{
							const node = dojo.place(`<span><BR>${_('Additional action(s):')}</span>`, 'generalactions');
							for (i = 0; i < args._private.additional; i++)
							{
								dojo.connect(dojo.place(`<span index='${i}' class=' ERAadditionalAction action-button bgabutton bgabutton_small bgabutton_blue'>${args._private.additionalOvalCost ? '-' + args._private.additionalOvalCost + ' DP' : _('free')}</span>`, node), 'click', (event) => {
									dojo.toggleClass(event.currentTarget, 'bgabutton_blue bgabutton_red');
									args._private.oval = this.last_server_state.args._private.oval + dojo.query('.ERAadditionalAction.bgabutton_red').length;
									$('ERAselectButton').innerHTML = dojo.string.substitute(_('Select Growth Actions (${oval})'), {oval: args._private.oval});
									dojo.toggleClass('ERAselectButton', 'disabled', !this.checkGrowthActions());
								});
							}
						}
						if (+args._private.square > 1)
						{
							if (+args._private.additionalSquareCost === 0)
								dojo.place(`<span id='ERAwarning' style='font-size:small;'><BR>${_('You can select a second square counter for free')}</span>`, 'generalactions');
							else
								dojo.place(`<span id='ERAwarning' style='font-size:small;'><BR>${dojo.string.substitute(_('You can select a second square counter for ${DP} DP'), {DP: args._private.additionalSquareCost})}</span>`, 'generalactions');
						}
						else dojo.place(`<span style='font-size:small;'><BR>${_('You must select a square counter')}</span>`, 'generalactions');
						break;
//
					case 'homeStarEvacuation':
//
						this.board.centerMap(this.gamedatas.factions[this.players[this.player_id]].homeStar + ':+0+0+0');
//
						if (args._private.voluntary) this.addActionButton('ERAundoButton', _('Cancel'), () => this.action('homeStarEvacuation', {color: this.color}));
						break;
//
					case 'blockMovement':
//
						this.board.centerMap(args._private.to);
//
						this.addActionButton('ERAcancelButton', _('Don`t block movement'), () => this.action('blockMovement'
									, {color: this.color, blocked: false}));
						this.addActionButton('ERAblockButton', _('Declare war and block movement'), () => this.action('blockMovement', {color: this.color, blocked: true}), null, false, 'red');
//
						break;
//
					case 'blockAction':
//
						this.board.centerMap(args._private.location);
//
						this.addActionButton('ERAcancelButton', dojo.string.substitute(_('Don`t block ${action}'), {action: this.GROWTHACTIONS[args._private.action][0]}), () => this.action('blockAction', {color: this.color, blocked: false}));
						this.addActionButton('ERAblockButton', dojo.string.substitute(_('Declare war and block ${action}'), {action: this.GROWTHACTIONS[args._private.action][0]}), () => this.action('blockAction', {color: this.color, blocked: true}), null, false, 'red');
//
						break;
//
					case 'blockHomeStarEvacuation':
//
						this.board.centerMap(args._private.location);
//
						this.addActionButton('ERAcancelButton', _('Don`t block home star evacuation'), () => this.action('blockAction', {color: this.color, blocked: false}));
						this.addActionButton('ERAblockButton', _('Declare war and home star evacuation'), () => this.action('blockAction', {color: this.color, blocked: true}), null, false, 'red');
//
						break;
//
					case 'resolveGrowthActions':
//
						this.board.centerMap(this.gamedatas.factions[this.players[this.player_id]].homeStar + ':+0+0+0');
//
						if (args.evacuation) this.addActionButton('ERAevacuationButton', _('Voluntary Home Star Evacuation'), () => this.action('homeStarEvacuation', {color: this.color}));
//
						if ('teleportPopulation' in args._private)
							this.addActionButton('ERAteleportButton', _('Teleport population') + ` (${args._private.teleportPopulation})`, () => {
								this.setClientState('teleportPopulation', {phase: 'from', descriptionmyturn: dojo.string.substitute(_('${you} can select up to ${population} population disc(s) to teleport'), {you: '${you}', population: args._private.teleportPopulation})});
							});
//
						this.addActionButton('ERApassButton', _('End turn'), () => {
							if (dojo.query('#ERAchoice .ERAcounter-growth').length)
							{
								this.confirmationDialog(_('You have unused growth action(s)'), () =>
								{
									this.action('pass', {color: this.color});
								});
							}
							else this.action('pass', {color: this.color});
						}, null, false, 'red');
						break;
//
					case 'stealTechnology':
//
						this.addActionButton('ERAcancelButton', _('No gain'), () => this.action('stealTechnology', {color: this.color, technology: ''}));
//
						break;
//
					case 'research':
//
						break;
//
					case 'researchPlus':
//
						this.addActionButton('ERAcancelButton', _('No Research+'), () => this.action('researchPlus', {color: this.color, technology: ''}), null, false, 'red');
//
						if (args._private.dominationCardExchange)
						{
							dojo.place(`<BR><span>${_('Exchange a domination card (do it first):')} </span>`, 'generalactions');
							for (let card of Object.values(args._private.hand))
							{
								this.addActionButton('ERAexchnageButton-' + card.id, this.DOMINATIONS[card.type].title, () => {
									this.action('dominationCardExchange', {color: this.color, id: card.id}, () => {
									});
								}
								);
							}
						}
//
						break;
//
					case 'growPopulation':
					case 'growPopulation+':
//
						this.addActionButton('ERAgrowPopulationButton', _('Confirm'), () => {
							this.setClientState('bonusPopulation', {descriptionmyturn: dojo.string.substitute(_('${you} may add ${bonus} “bonus population” discs'), {you: '${you}', bonus: args._private.bonusPopulation})});
						});
//
						break;
//
					case 'bonusPopulation':
//
						if (args._private.ancientPyramids)
						{
							const node = dojo.place(`<div id='ERAancientPyramids' class='ERAcounter ERAcounter-relic ERAcounter-0 action-button' style='display:inline-block;vertical-align:middle;'></div>`, 'generalactions');
							this.addTooltip(node, _('Ancient Pyramids'), _('Place a bonus population here'));
							dojo.connect(node, 'click', () => this.bonusPopulation(args._private.ancientPyramids));
						}
//
						this.addActionButton('ERAbonusPopulationButton', _('Confirm'), () => {
							const locations = dojo.query('.ERAprovisional').reduce((L, node) => [...L, node.getAttribute('location')], []);
							const locationsBonus = dojo.query('.ERAprovisionalBonus').reduce((L, node) => [...L, node.getAttribute('location')], []);
//
							const population = locations.length + locationsBonus.length - args._private.population;
//
							const counter = $('ERAchoice').querySelector('.ERAcounter.ERAselected');
							if (counter)
							{
								if (population > 0)
								{
									this.confirmationDialog(dojo.string.substitute(_('You will have to remove ${population} population disc(s)'), {population: population}), () =>
									{
										this.action('growPopulation', {color: this.color, locations: JSON.stringify(locations), locationsBonus: JSON.stringify(locationsBonus), locationsRemoved: '[]', bonus: counter.getAttribute('counter') === 'growPopulation+'});
										dojo.query('.ERAcounter-populationDisc.ERAprovisional,.ERAprovisionalBonus', 'ERAboard').remove().forEach((node) => this.counters.arrange(dojo.getAttr(node, 'location')));
									});
								}
								else
								{
									this.action('growPopulation', {color: this.color, locations: JSON.stringify(locations), locationsBonus: JSON.stringify(locationsBonus), locationsRemoved: '[]', bonus: counter.getAttribute('counter') === 'growPopulation+'});
									dojo.query('.ERAcounter-populationDisc.ERAprovisional,.ERAprovisionalBonus', 'ERAboard').remove().forEach((node) => this.counters.arrange(dojo.getAttr(node, 'location')));
								}
							}
						}, null, false, 'red');
						break;
//
					case 'removePopulation':
//
						{
							this.addActionButton('ERAcancelButton', _('Cancel'), () => this.restoreServerGameState());
//
							const populationNode = dojo.place(`<div id='ERAremovePopulation' style='display:inline-flex;flex-direction:row;align-items:center;vertical-align:middle;margin-left:10px;'></div>`, 'generalactions');
							for (let i = 0; i < args.population; i++)
							{
								const node = dojo.place(`<div class='ERAcounter ERAcounter-${this.color} ERAcounter-populationDisc ERAsmall' location='' style='pointer-events:all !important;'></div>`, 'ERAremovePopulation');
								dojo.connect(node, 'click', (event) => {
									dojo.stopEvent(event);
									if (dojo.hasClass(event.currentTarget, 'ERAdisabled'))
									{
										const location = dojo.getAttr(event.currentTarget, 'location');
										const nodes = dojo.query(`.ERAcounter-populationDisc[location='${location}'].ERAdisabled`, 'ERAboard');
										if (nodes.length)
										{
											dojo.removeClass(nodes.pop(), 'ERAdisabled');
											dojo.removeClass(event.currentTarget, 'ERAdisabled');
											dojo.setAttr(event.currentTarget, 'location', '');
											dojo.addClass('ERAremovePopulationButton', 'disabled');
										}
									}
								});
							}
//
							this.addActionButton('ERAremovePopulationButton', _('Confirm'), () => {
								const locationsRemoved = dojo.query(`.ERAcounter-populationDisc.ERAdisabled`, 'ERAremovePopulation').reduce((L, node) => [...L, node.getAttribute('location')], []);
								if (locationsRemoved.length === args.population) this.action('removePopulation', {color: this.color, locations: JSON.stringify(locationsRemoved)});
							}, null, false, 'red');
							dojo.addClass('ERAremovePopulationButton', 'disabled');
						}
						break;
//
					case 'teleportPopulation':
//
						{
							const populationNode = dojo.place(`<div id='ERAteleportPopulation' style='display:inline-flex;flex-direction:row;align-items:center;vertical-align:middle;margin-left:10px;'></div>`, 'generalactions');
							this.addActionButton('ERAteleportButton', _('Select destination'), (event) => {
								if (dojo.hasClass(event.currentTarget, 'bgabutton_blue'))
								{
									event.currentTarget.innerHTML = _('Teleport discs');
									dojo.removeClass(event.currentTarget, 'bgabutton_blue');
									dojo.addClass(event.currentTarget, 'bgabutton_red disabled');
								}
								else
								{
									const from = dojo.query(`.ERAcounter-populationDisc.ERAdisabled`, 'ERAteleportPopulation').reduce((L, node) => [...L, node.getAttribute('location')], []);
									const to = dojo.query('.ERAprovisional', 'ERAboard').reduce((L, node) => [...L, node.getAttribute('location')], []);
									this.action('teleportPopulation', {color: this.color, from: JSON.stringify(from), to: JSON.stringify(to)});
									dojo.query('.ERAcounter-populationDisc.ERAprovisional', 'ERAboard').remove().forEach((node) => this.counters.arrange(dojo.getAttr(node, 'location')));
								}
							});
							dojo.addClass('ERAteleportButton', 'disabled');
						}
						break;
//
					case 'gainStar':
					case 'gainStar+':
//
						break;
//
					case 'buildShips':
					case 'buriedShips':
					case 'emergencyReserve':
//
						if (!this.on_client_state) this.addActionButton('ERAcancelButton', _('Cancel'), () => this.restoreServerGameState());
//
						this.location = (args._private.stars.length === 1) ? args._private.stars[0] : undefined;
//
						const fleetNode = dojo.place(`<div id='ERAbuildShips' class='ERAships' style='display:inline-flex;flex-direction:row;align-items:center;vertical-align:middle;'></div>`, 'generalactions');
						dojo.style(fleetNode, 'margin', '0px 25px 0px 10px');
						for (let i = 0; i < args._private.newShips; i++)
						{
							const shipNode = dojo.place(`<div id='ERAbuildShip' style='width:50px;height:50px;transform:scale(20%);transform-origin:left top;margin-right:-30px'></div>`, fleetNode);
							const node = dojo.place(this.format_block('ERAship', {id: 'buildShip', color: args.active, location: ''}), shipNode);
						}
						if (this.location) this.fleets(this.location, 'ships', dojo.query(`.ERAships .ERAship`));
//
						this.addActionButton('ERAbuildShipsButton', _('Confirm'), () => {
//
							const buildShips = {ships: [], fleets: {}};
							dojo.query('.ERAprovisional').forEach((node) => {
								const fleet = dojo.getAttr(node, 'fleet');
								if (fleet) buildShips.fleets[fleet] = node.getAttribute('location');
								else buildShips.ships.push(node.getAttribute('location'));
							});
//
							if ($('ERAbuildShip'))
							{
								this.confirmationDialog(_('You have unbuild ship(s)'), () =>
								{
									this.action('buildShips', {color: this.color, buildShips: JSON.stringify(buildShips)});
								});
							}
							else this.action('buildShips', {color: this.color, buildShips: JSON.stringify(buildShips)});
							dojo.query('.ERAprovisional').remove().forEach((node) => this.ships.arrange(dojo.getAttr(node, 'location')));
						}, null, false, 'red');
//
						break;
//
					case 'tradingPhase':
//
						this.addActionButton('ERApassButton', _('No trade'), () => {
							const node = $('ERApassButton');
							if (node.count)
							{
								delete node.count;
								return this.restoreServerGameState();
							}
							dojo.query('#generalcounters .bgabutton:not(#ERApassButton)').remove();
							node.count = 1;
							const timer = window.setInterval(() => {
								node.count -= 1;
								node.innerHTML = _('Cancel') + ` (${(+node.count / 10).toFixed(1)}s)`;
								if (node.count < 0)
								{
									window.clearInterval(timer);
									return this.action('trade', {from: args._private.color, to: '', technology: ''});
								}
							}, 100);
						}, null, false, 'red');
				}
			}
		},
		setupNotifications: function ()
		{
			console.log('notifications subscriptions setup');
//
			dojo.subscribe('GODMODE', (notif) => {
				this.gamedatas.GODMODE = +notif.args.GODMODE;
				this.restoreServerGameState();
			});
//
			dojo.subscribe('updateScoring', (notif) => {
				for (let color in notif.args.scoring)
				{
					this.gamedatas.factions[color].scoring = notif.args.scoring[color];
					if (this.color === color)
					{
						dojo.query('.ERAdominationCard', `ERAdominationCards-${color}`).forEach((node) => {
							if (this.gamedatas.factions[color].scoring[node.getAttribute('domination')].A) dojo.style(node, 'box-shadow', `0px 0px 5px 4px #${color}`);
							else dojo.style(node, 'box-shadow', '');
						});
						dojo.query('.ERAdominationCard', `ERAplayerDominationCards-${color}`).forEach((node) => {
							if (this.gamedatas.factions[color].scoring[node.getAttribute('domination')].A) dojo.style(node, 'box-shadow', `0px 0px 5px 4px #${color}`);
							else dojo.style(node, 'box-shadow', '');
						});
					}
				}
			});
//
			dojo.subscribe('update_score', (notif) => this.scoreCtrl[notif.args.player_id].setValue(notif.args.score));
			dojo.subscribe('updateRound', (notif) => this.updateRound(notif.args.round));
			dojo.subscribe('updateFaction', (notif) => this.factions.update(notif.args.faction));
			dojo.subscribe('playDomination', (notif) => this.playDomination(notif.args.card, notif.args.section));
//
			dojo.subscribe('peace', (notif) => this.peace(notif.args.from));
//
			dojo.subscribe('placeCounter', (notif) => this.counters.place(notif.args.counter));
			dojo.subscribe('flipCounter', (notif) => this.counters.flip(notif.args.counter));
			dojo.subscribe('removeCounter', (notif) => this.counters.remove(notif.args.counter));
//
			dojo.subscribe('placeShip', (notif) => this.ships.place(notif.args.ship));
			dojo.subscribe('moveShips', (notif) => this.ships.move(notif.args.ships, notif.args.location));
			dojo.subscribe('revealShip', (notif) => this.ships.reveal(notif.args.ship));
			dojo.subscribe('removeShip', (notif) => this.ships.remove(notif.args.ship));
			dojo.subscribe('homeStarEvacuation', (notif) => this.ships.homeStarEvacuation(notif.args.homeStar, notif.args.location));
//
			this.notifqueue.setIgnoreNotificationCheck('msg', (notif) => (this.player_id === +notif.args.player_id));
			this.notifqueue.setIgnoreNotificationCheck('revealShip', (notif) => (this.player_id === +notif.args.player_id));
			this.notifqueue.setIgnoreNotificationCheck('updateFaction', (notif) => (this.player_id === +notif.args.player_id));
//
			this.setSynchronous();
		},
		setSynchronous()
		{
			this.notifqueue.setSynchronous('updateRound', DELAY);
			this.notifqueue.setSynchronous('updateFaction', DELAY / 2);
			this.notifqueue.setSynchronous('playDomination', DELAY);
//
			this.notifqueue.setSynchronous('placeCounter', DELAY / 2);
			this.notifqueue.setSynchronous('flipCounter', DELAY);
			this.notifqueue.setSynchronous('removeCounter', DELAY / 4);
//
			this.notifqueue.setSynchronous('placeShip', DELAY);
			this.notifqueue.setSynchronous('moveShips', DELAY / 2);
//			this.notifqueue.setSynchronous('revealShip', DELAY / 2);
			this.notifqueue.setSynchronous('removeShip', DELAY / 4);
			this.notifqueue.setSynchronous('homeStarEvacuation', DELAY);
//
		},
		focus: function (node)
		{
			if (dojo.hasClass(node, 'ERAfocus'))
			{
				dojo.style(node, {'pointer-events': 'none', 'z-index': '1000', 'transform': ``});
				dojo.removeClass(node, 'ERAfocus');
			}
			else
			{
				dojo.query('.ERAfocus').forEach((node) => {
					dojo.style(node, {'pointer-events': 'none', 'z-index': '1000', 'transform': ``});
					dojo.removeClass(node, 'ERAfocus');
				});
				const rect = node.getBoundingClientRect();
				const zoom = window.getComputedStyle($('page-content')).zoom || 1;
				dojo.style(node, {
					'pointer-events': 'none', 'z-index': '1000',
					'transform': `translate(${(window.innerWidth / zoom / 2 - rect.x - rect.width / 2)}px,${(window.innerHeight / zoom / 2 - rect.y - rect.height / 2)}px) scale(${.75 * window.innerHeight / rect.height})`,
					'transform-origin': 'center'
				});
				dojo.addClass(node, 'ERAfocus');
			}
		},
		updateRound: function (round)
		{
			if (round > 0)
			{
				dojo.style('ERApawn', 'left', (84.7 * round) + 'px');
				dojo.setAttr('ERApawn', 'title', dojo.string.substitute(_('Round ${round} of 8'), {round: round}));
			}
		},
		peace: function (from)
		{
			const otherName = $(`player_name_${this.gamedatas.factions[from].player_id}`).children[0].outerHTML;
			this.confirmationDialog(dojo.string.substitute(_('${player_name} wants to make peace'), {player_name: `<span style='background:black'>${otherName}</span>`}), () =>
			{
				this.action('acceptPeace', {color: this.color, from: from});
			}, () => {
				this.action('rejectPeace', {color: this.color, from: from});
			});
			dojo.query('.standard_popin_underlay').style('visibility', 'hidden');
		},
		playDomination: function (card, section)
		{
			const node = dojo.place(this.format_block('ERAdominationCard', {id: section, index: card.id, domination: card.type, owner: card.location_arg}), `ERAtechTrack-${card.location_arg}`);
			dojo.setAttr(node.querySelector('img'), 'src', `${g_gamethemeurl}img/dominationCards/${card.type}.jpg`);
			dojo.addClass(node, `ERAdominationCard-${section}`);
			dojo.connect(node, 'click', (event) => {
				dojo.stopEvent(event);
				this.focus(event.currentTarget);
			});
			dojo.connect(node, 'transitionend', () => dojo.style(node, {'pointer-events': '', 'z-index': ''}));
		},
		remoteViewing: function (type, node)
		{
			switch (type)
			{
				case 'counter':
					return this.action('remoteViewing', {color: this.color, ancientPyramids: this.gamedatas.gamestate.args.ancientPyramids, type: 'counter', id: dojo.getAttr(node, 'counter')});
				case 'fleet':
					return this.action('remoteViewing', {color: this.color, ancientPyramids: this.gamedatas.gamestate.args.ancientPyramids, type: 'fleet', id: dojo.getAttr(node, 'ship')});
				case 'dominationCard':
					return this.action('remoteViewing', {color: this.color, ancientPyramids: this.gamedatas.gamestate.args.ancientPyramids, type: 'dominationCard', id: 0});
			}
		},
		fleets: function (location, type, nodes)
		{
//
			dojo.toggleClass('ERAfleets', 'ERAhide', nodes.length === 0);
			dojo.query('.ERAship:not(.ERAprovisional)', 'ERAfleets').removeClass('ERAselectable ERAselected');
			if (nodes.length === 0) return;
//
			switch (type)
			{
//
				case 'ships':
//
					dojo.query('#ERAfleets .ERAfleet').forEach((node) => {
						let hide = true;
						let fleetLocation = dojo.getAttr(node, 'location');
						if (fleetLocation === location || (fleetLocation === 'stock' && 'stars' in this.gamedatas.gamestate.args._private && this.gamedatas.gamestate.args._private.stars.includes(location)))
						{
							hide = false;
							node.querySelector('.ERAfleetAction').innerHTML = '⟱';
							dojo.query('.ERAship[fleet]', node).addClass('ERAselectable');
						}
						dojo.toggleClass(node, 'ERAhide', hide);
					});
					break;
//
				case 'fleet':
//
					const fleet = dojo.getAttr(nodes[0], 'fleet');
					dojo.query('#ERAfleets .ERAfleet').forEach((node) => {
						let hide = true;
						let fleetLocation = dojo.getAttr(node, 'location');
						if (fleet !== dojo.getAttr(node, 'fleet'))
						{
							if (fleetLocation === location)
							{
								node.querySelector('.ERAfleetAction').innerHTML = '⇚⇛';
								dojo.query('>.ERAship', node).addClass('ERAselectable');
								hide = false;
							}
							else if ('stars' in this.gamedatas.gamestate.args._private && this.gamedatas.gamestate.args._private.stars.includes(location))
							{
								if (fleetLocation === 'stock') hide = false;
								if (this.gamedatas.gamestate.args._private.stars.includes(fleetLocation)) hide = false;
								node.querySelector('.ERAfleetAction').innerHTML = '⇚⇛';
								dojo.query('>.ERAship', node).addClass('ERAselectable');
							}
						}
						else
						{
							hide = false;
							node.querySelector('.ERAfleetAction').innerHTML = '⤊';
							dojo.query('.ERAship', node).addClass('ERAselectable');
						}
						dojo.toggleClass(node, 'ERAhide', hide);
					});
					break;
			}
		},
		homeStarEvacuation: function (location)
		{
			if (this.gamedatas.gamestate.args._private.evacuate.includes(location)) this.action('homeStarEvacuation', {color: this.color, location: JSON.stringify(location)});
		},
		combatChoice: function (location)
		{
			if (this.gamedatas.gamestate.args.combatChoice.includes(location)) this.action('combatChoice', {color: this.color, location: JSON.stringify(location)});
		},
		retreat: function (location)
		{
			if (this.gamedatas.gamestate.args.retreat.includes(location)) this.action('retreat', {color: this.gamedatas.gamestate.args.active, location: JSON.stringify(location)});
		},
		gainStar: function (location)
		{
			if (location in this.gamedatas.gamestate.args._private.gainStar)
			{
				const counter = $('ERAchoice').querySelector('.ERAcounter.ERAselected');
				if (counter.getAttribute('counter') === 'gainStar+' && location[0] !== '0') return;
				const locationsRemoved = dojo.query(`.ERAcounter-populationDisc.ERAdisabled`, 'ERAremovePopulation').reduce((L, node) => [...L, node.getAttribute('location')], []);
				if (this.gamedatas.gamestate.args._private.gainStar[location] && !this.gamedatas.factions[this.color].atWar.includes(this.gamedatas.gamestate.args._private.gainStar[location]))
				{
					const otherName = $(`player_name_${this.gamedatas.factions[this.gamedatas.gamestate.args._private.gainStar[location]].player_id}`).children[0].outerHTML;
					this.confirmationDialog(dojo.string.substitute(_('You must declare war on ${on} to gain this star'), {on: `<span style='background:black'>${otherName}</span>`}), () =>
					{
						this.action('gainStar', {color: this.color, location: JSON.stringify(location), locationsRemoved: JSON.stringify(locationsRemoved), center: counter.getAttribute('counter') === 'gainStar+'});
					});
				}
				else this.action('gainStar', {color: this.color, location: JSON.stringify(location), locationsRemoved: JSON.stringify(locationsRemoved), center: counter.getAttribute('counter') === 'gainStar+'});
			}
			;
		},
		buildShips: function (location, fleet = 'ship')
		{
			if (fleet === 'ship')
			{
				if (this.location === location)
				{
					const shipNode = $('ERAbuildShip');
					if (shipNode)
					{
						if (dojo.query(`>.ERAship[color='FF3333']:not([fleet])`, 'ERAboard').length >= 16) return this.showMessage(_('No more ship minis'), 'error');
//
						dojo.destroy(shipNode);
//
						const node = this.ships.place({id: '', color: this.color, fleet: 'ship', location: location});
						dojo.addClass(node, 'ERAselectable ERAprovisional');
					}
					return;
				}
			}
			const node = $('ERAstars').querySelector(`path[id='${location}`);
			if (node)
			{
				this.location = location;
//
				$('ERAstars').querySelectorAll('path').forEach((node) => node.setAttribute('class', ''));
				node.setAttribute('class', 'ERAselected');
//
				this.fleets(this.location, 'ships', dojo.query(`.ERAships .ERAship`));
		}
		},
		growPopulation: function (location)
		{
			if (location in this.gamedatas.gamestate.args._private.growPopulation)
			{
				let nodes = dojo.query(`.ERAcounter-populationDisc[location='${location}'].ERAprovisional`);
				if (nodes.length)
				{
					nodes.remove();
					this.counters.arrange(location);
				}
				else
				{
					for (let i = 0; i < (this.gamedatas.factions[this.color].starPeople === 'Mantids' ? 2 : 1); i++)
					{
						const population = +this.gamedatas.gamestate.args._private.growPopulation[location].population + i;
						const limit = +this.gamedatas.gamestate.args._private.growPopulation[location].growthLimit;
						if (population < limit) dojo.addClass(this.counters.place({id: 'growPopulation', color: this.color, type: 'populationDisc', location: location}), 'ERAprovisional');
					}
				}
			}
		},
		bonusPopulation: function (location)
		{
			const ancientPyramids = this.gamedatas.gamestate.args._private.ancientPyramids;
//
			if (location in this.gamedatas.gamestate.args._private.growPopulation)
			{
				let nodes = dojo.query(`.ERAcounter-populationDisc[location='${location}'].ERAprovisionalBonus`);
				if (nodes.length)
				{
					nodes.remove();
					this.counters.arrange(location);
				}
				else
				{
					if (location == ancientPyramids || dojo.query(`.ERAcounter-populationDisc.ERAprovisionalBonus:not([location='${ancientPyramids}'])`).length < this.gamedatas.gamestate.args._private.bonusPopulation)
						dojo.addClass(this.counters.place({id: 'growPopulation', color: this.color, type: 'populationDisc', location: location}), 'ERAprovisionalBonus');
				}
			}
			if (ancientPyramids) dojo.toggleClass('ERAancientPyramids', 'ERAdisabled', dojo.query(`.ERAcounter-populationDisc.ERAprovisionalBonus[location='${ancientPyramids}']`).length > 0);
		},
		teleportPopulation: function (location)
		{
			if (this.gamedatas.gamestate.args._private.populations.includes(location))
			{
				if (dojo.hasClass('ERAteleportButton', 'bgabutton_red'))
				{
					const nodes = dojo.query(`.ERAcounter-populationDisc:not(.ERAdisabled)`, 'ERAteleportPopulation');
					if (nodes.length)
					{
						dojo.addClass(this.counters.place({id: 'teleportPopulation', color: this.color, type: 'populationDisc', location: location}), 'ERAprovisional');
						dojo.addClass(nodes.pop(), 'ERAdisabled');
					}
					dojo.toggleClass('ERAteleportButton', 'disabled', dojo.query(`.ERAcounter-populationDisc:not(.ERAdisabled)`, 'ERAteleportPopulation').length !== 0);
				}
				if (dojo.hasClass('ERAteleportButton', 'bgabutton_blue'))
				{
					const nodes = dojo.query(`.ERAcounter-populationDisc[location='${location}']:not(.ERAdisabled)`, 'ERAboard');
					if (nodes.length && dojo.query(`.ERAcounter-populationDisc`, 'ERAteleportPopulation').length < this.gamedatas.gamestate.args._private.teleportPopulation)
					{
						dojo.addClass(nodes.pop(), 'ERAdisabled');
						const node = dojo.place(`<div class='ERAcounter ERAcounter-${this.color} ERAcounter-populationDisc ERAsmall' location='${location}'></div>`, 'ERAteleportPopulation');
						dojo.connect(node, 'click', (event) => {
							dojo.stopEvent(event);
							if (dojo.hasClass('ERAteleportButton', 'bgabutton_blue'))
							{
								const nodes = dojo.query(`.ERAcounter-populationDisc[location='${location}'].ERAdisabled`);
								if (nodes.length)
								{
									dojo.removeClass(nodes.pop(), 'ERAdisabled');
									dojo.destroy(node);
								}
								dojo.toggleClass('ERAteleportButton', 'disabled', dojo.query(`.ERAcounter-populationDisc`, 'ERAteleportPopulation').length === 0);
							}
						});
						dojo.toggleClass('ERAteleportButton', 'disabled', dojo.query(`.ERAcounter-populationDisc`, 'ERAteleportPopulation').length === 0);
					}
				}
			}
		},
		removePopulation: function (location)
		{
			if (this.gamedatas.gamestate.args._private.populations.includes(location))
			{
				const locationsRemoved = dojo.query(`.ERAcounter-populationDisc.ERAdisabled`, 'ERAremovePopulation').reduce((L, node) => [...L, node.getAttribute('location')], []);
//
				let population = locationsRemoved.length;
//
				const nodes1 = dojo.query(`.ERAcounter-populationDisc[location='${location}']:not(.ERAdisabled)`, 'ERAboard');
				const nodes2 = dojo.query(`.ERAcounter-populationDisc:not(.ERAdisabled)`, 'ERAremovePopulation');
				if (nodes1.length && nodes2.length)
				{
					dojo.addClass(nodes1.pop(), 'ERAdisabled');
					node = nodes2.pop();
					dojo.addClass(node, 'ERAdisabled');
					dojo.setAttr(node, 'location', location);
					population++;
				}
				dojo.toggleClass('ERAremovePopulationButton', 'disabled', this.gamedatas.gamestate.args.population !== population);
			}
		},
		planetaryDeathRay: function (location, target)
		{
			if (this.gamedatas.gamestate.args._private.planetaryDeathRayTargets.includes(location))
			{
				if (dojo.hasClass(target, 'ERAcounter-populationDisc')) this.action('planetaryDeathRay', {color: this.color, type: 'disc', id: dojo.getAttr(target, 'counter')});
				if (dojo.hasClass(target, 'ERAship')) this.action('planetaryDeathRay', {color: this.color, type: 'ship', id: dojo.getAttr(target, 'ship')});
			}
		},
		battleLoss: function (totalVictory)
		{
			const winner = dojo.query('.ERAship:not([fleet])', 'ERAwinner').length;
			const winnerLose = dojo.query('.ERAship:not([fleet]).ERAselected', 'ERAwinner').length;
			const losersLose = dojo.query('.ERAship:not([fleet]).ERAselected', 'ERAlosers').length;
			const toDestroy = totalVictory ? 0 : Math.min(winner, Math.ceil(losersLose / 2));
//
			$('ERAwinnerLose').innerHTML = winnerLose;
			$('ERAlosersLose').innerHTML = losersLose;
			if (!totalVictory) $('ERAtoDestroy').innerHTML = dojo.string.substitute(_('You must destroy ${N} of your ships'), {N: toDestroy});
//
			dojo.toggleClass('ERAdone', 'disabled', winnerLose !== toDestroy);
		},
		checkGrowthActions: function ()
		{
			if (+this.gamedatas.GODMODE === 1) return true;
//
			const oval = dojo.query('#ERAchoice .ERAcounter-growth.ERAselected').length;
			const square = dojo.query('#ERAchoice .ERAcounter-technology.ERAselected,#ERAchoice .ERAcounter-turnOrder.ERAselected').length;
//
			return oval === +this.gamedatas.gamestate.args._private.oval && square >= 1 && square <= +this.gamedatas.gamestate.args._private.square;
		},
		onCenter: function (event)
		{
			if (event.currentTarget.hasAttribute('location'))
			{
				dojo.stopEvent(event);
				this.board.centerMap(event.currentTarget.getAttribute('location'));
			}
		},
		onScreenWidthChange: function ()
		{
			dojo.query('.ERAfocus').forEach((node) => {
				dojo.style(node, {'pointer-events': 'none', 'z-index': '1000', 'transform': ``});
				dojo.removeClass(node, 'ERAfocus');
			});
		},
		format_string_recursive: function (log, args)
		{
			if (log && args && !args.processed)
			{
				args.processed = true;
//
				if ('GPS' in args) {
					if (!this.isCurrentPlayerActive()) this.board.centerMap(args.GPS);
					args.GPS = `<span onclick="gameui.onCenter(event)" location='${args.GPS}'>📌</span>`;
				}
//
				if ('DICE' in args) args.DICE = `<span class='ERAdice' style='background-position-x:-${30 * (args.DICE - 1)}px'></span>`;
				if ('DICE1' in args) args.DICE1 = `<span class='ERAdice' style='background-position-x:-${30 * (args.DICE1 - 1)}px'></span>`;
				if ('DICE2' in args) args.DICE2 = `<span class='ERAdice' style='background-position-x:-${30 * (args.DICE2 - 1)}px'></span>`;
//
				if ('RANKING' in args)
					args.RANKING = `<div style='text-align:center;'><img src='${g_gamethemeurl}/img/ranking/${args.RANKING}.png' style='height:50px;vertical-align:middle;filter:invert(1)'></img><span> ${this.RANKINGS[args.RANKING]} </span><img src='${g_gamethemeurl}/img/ranking/${args.RANKING}.png' style='height:50px;vertical-align:middle;filter:invert(1)'></img></div>`;
			}
			return this.inherited(arguments);
		},
		updatePreference: function (event)
		{
			const match = event.target.id.match(/^preference_[cf]ontrol_(\d+)$/);
//
			if (match)
			{
				let pref = +match[1];
				let value = +event.target.value;
				this.prefs[pref].value = value;
				switch (pref)
				{
					case SPEED:
						DELAY = DELAYS[value];
						document.documentElement.style.setProperty('--DELAY', DELAY);
						this.setSynchronous();
						break;
				}
			}
		},
		action: function (action, args =
		{}, success = () => {}, fail = undefined)
		{
			if ((action === 'GODMODE')) return this.ajaxcall(`/galacticera/galacticera/${action}.html`, args, this, success, fail);
			if ((action === 'domination')) return this.ajaxcall(`/galacticera/galacticera/${action}.html`, args, this, success, fail);
			if ((action === 'declarePeace')) return this.ajaxcall(`/galacticera/galacticera/${action}.html`, args, this, success, fail);
			if ((action === 'acceptPeace')) return this.ajaxcall(`/galacticera/galacticera/${action}.html`, args, this, success, fail);
			if ((action === 'rejectPeace')) return this.ajaxcall(`/galacticera/galacticera/${action}.html`, args, this, success, fail);
			if ((action === 'trade' && this.checkPossibleActions(action)) || this.checkAction(action))
			{
				args.lock = true;
				this.ajaxcall(`/galacticera/galacticera/${action}.html`, args, this, success, fail);
		}
		}
	}
	);
}
);
