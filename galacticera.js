/**
 *
 * @author Lunalol
 */
define(["dojo", "dojo/_base/declare", "ebg/core/gamegui", "ebg/counter",
	g_gamethemeurl + "modules/JavaScript/translations.js",
	g_gamethemeurl + "modules/JavaScript/constants.js",
	g_gamethemeurl + "modules/JavaScript/board.js",
	g_gamethemeurl + "modules/JavaScript/factions.js",
	g_gamethemeurl + "modules/JavaScript/counters.js",
	g_gamethemeurl + "modules/JavaScript/ships.js"
], function (dojo, declare)
{
	return declare("bgagame.galacticera", ebg.core.gamegui,
	{
		constructor: function ()
		{
			console.log('galacticera constructor');
//
			this.default_viewport = 'initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,width=device-width,user-scalable=no';
//
			this.dontPreloadImage('background.jpg');
		},
		setup: function (gamedatas)
		{
			console.log("Starting game setup");
			console.debug(gamedatas);
//
			dojo.destroy('debug_output');
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
//
// Setup game Board
//
			this.board = new Board(this);
			this.factions = new Factions(this);
			this.counters = new Counters(this);
			this.ships = new Ships(this);
//
// Setup factions
//
			for (let faction of Object.values(gamedatas.factions))
			{
				if (faction.player_id <= 0)
				{
					let node = dojo.place(`<div id='overall_player_board_${faction.player_id}' class='player-board' id='player_board_${faction.player_id}'/></div>`, 'player_boards');
					dojo.place(`<div class='player-name' id='player_name_${faction.player_id}' style='color:#${faction.color}'>${{0: _('Automa'), 1: _('Genetic Farmers'), 2: _('Slavers')}[ -faction.player_id]}</div>`, node);
					dojo.place(`<div id='player_board_${faction.player_id}' class='player-board_content' id='player_board_${faction.player_id}'/></div>`, node);
				}
//
// Setup player panels
//
				let nodeCounters = dojo.place(`<div class='ERAcounters' id='ERAcounters-${faction.color}'></div>`, `player_board_${faction.player_id}`, 2);
//
				let nodeStatus = dojo.place(`<div id='ERAstatus-${faction.color}' class='ERAstatus'></div>`, nodeCounters, 'after');
				dojo.place(`<div class='ERAsmall ERAcounter ERAselectable ERAorder' title='${_('Turn order')}' id='ERAorder-${faction.color}' faction='${faction.color}'></div>`, nodeStatus);
//
				for (let otherFaction of Object.values(gamedatas.factions))
				{
					if (otherFaction.color !== faction.color)
					{
						dojo.connect(dojo.place(`<div id='ERApeace-${faction.color}-${otherFaction.color}' class='ERAsmall ERAcounter ERAcounter-${otherFaction.color} ERAcounter-peace' color='${faction.color}' on='${otherFaction.color}'  title='${_('Declare war')}'></div>`, nodeStatus), 'click', (event) => {
							dojo.stopEvent(event);
							const name = $(`player_name_${otherFaction.player_id}`).innerHTML;
							this.confirmationDialog(dojo.string.substitute(_('Declare war on ${on}'), {on: `<span style='background:#${otherFaction.color}'>${name}</span>`}), () =>
							{
								this.action('declareWar', {color: faction.color, on: otherFaction.color});
							});
						});
						dojo.connect(dojo.place(`<div id='ERAwar-${faction.color}-${otherFaction.color}'  class='ERAsmall ERAcounter ERAcounter-${otherFaction.color} ERAcounter-war' color='${faction.color}' on='${otherFaction.color}'></div>`, nodeStatus), 'click', (event) => {
							dojo.stopEvent(event);
							const name = $(`player_name_${otherFaction.player_id}`).innerHTML;
							this.confirmationDialog(dojo.string.substitute(_('Propose peace to ${on}'), {on: `<span style='background:#${otherFaction.color}'>${name}</span>`}), () =>
							{
								this.action('declarePeace', {color: faction.color, on: otherFaction.color});
							});
						});
					}
				}
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
							dojo.place(`<div class='ERAcounter ERAcounter-${otherFaction.color} ERAcounter-peace' color='${faction.color}' on='${otherFaction.color}' title='${_('Declare war')}'></div>`, container);
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
				let nodePopulation = dojo.place(`<div>${'Population disks'} : <span id='ERApopulation-${faction.color}'>?</span>/39</div>`, nodeTechnologies);
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
					dojo.place(`<div><span class='ERAtechnology' title='${_(technology)}' technology=${technology}>?</span><div class='ERAsmallerTechnology'><div class='ERAcounter ERAcounter-technology' counter='${technology}'/></div>`, nodeTechnologies);
				}
//
				this.factions.update(faction);
//
				dojo.connect($(`player_board_${faction.player_id}`), 'click', () => this.board.home(faction.player_id));
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
					dojo.style(event.currentTarget, {'pointer-events': '', 'z-index': ''})
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
// Panels order
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
//
			dojo.query('.ERAcounters').empty();
			if ('counters' in state.args)
			{
				for (color in state.args.counters)
				{
					for (let counter of state.args.counters[color].available)
					{
						switch (counter)
						{
							case 'Military':
							case 'Spirituality':
							case 'Propulsion':
							case 'Robotics':
							case 'Genetics':
								node = dojo.place(`<div class='ERAsmallTechnology' title='${_(counter)}'><div class='ERAcounter ERAcounter-technology' counter='${counter}'/></div>`, `ERAcounters-${color}`);
								break;
							default:
								node = dojo.place(`<div class='ERAsmallGrowth'><div class='ERAcounter ERAcounter-${color} ERAcounter-growth' counter='${counter}'/></div>`, `ERAcounters-${color}`);
						}
					}
					for (let counter of state.args.counters[color].used)
					{
						switch (counter)
						{
							case 'Military':
							case 'Spirituality':
							case 'Propulsion':
							case 'Robotics':
							case 'Genetics':
								node = dojo.place(`<div class='ERAsmallTechnology' title='${_(counter)}' style='filter:grayscale(1);'><div class='ERAcounter ERAcounter-technology' counter='${counter}'/></div>`, `ERAcounters-${color}`);
								break;
							default:
								node = dojo.place(`<div class='ERAsmallGrowth' style='filter:grayscale(1);'><div class='ERAcounter ERAcounter-${color} ERAcounter-growth' counter='${counter}'/></div>`, `ERAcounters-${color}`);
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
				for (const counter of state.args._private.counters)
				{
					switch (counter)
					{
						case 'Military':
						case 'Spirituality':
						case 'Propulsion':
						case 'Robotics':
						case 'Genetics':
							{
								const container = dojo.place('<div></div>', technologyNode);
								const node = dojo.place(this.format_block('ERAcounter', {id: 'counters-' + counter, color: state.args._private.color, type: 'technology', location: ''}), container);
								dojo.setAttr(node, 'counter', counter);
								dojo.addClass(node, 'ERAselectable');
								dojo.connect(node, 'click', (event) => {
									if (this.isCurrentPlayerActive())
									{
										if (stateName === 'selectCounters')
										{
											if (state.args._private.square > 1)
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
										if (stateName === 'individualChoice')
										{
											this.action('individualChoice', {color: this.color, technology: counter});
										}
									}
								});
							}
							break;
						case 'changeTurnOrderUp':
						case 'changeTurnOrderDown':
							{
								const container = dojo.place('<div></div>', turnOrderNode);
								const node = dojo.place(this.format_block('ERAcounter', {id: 'counters-' + counter, color: state.args._private.color, type: 'turnOrder', subtype: counter, location: ''}), container);
								dojo.setAttr(node, 'counter', counter);
								dojo.addClass(node, 'ERAselectable');
								dojo.connect(node, 'click', (event) => {
									if (this.isCurrentPlayerActive())
									{
										if (state.args._private.square > 1)
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
							const node = dojo.place(this.format_block('ERAcounter', {id: 'counters-' + counter, color: state.args._private.color, type: 'growth', subtype: counter, location: ''}), container);
							dojo.setAttr(node, 'counter', counter);
							dojo.addClass(node, 'ERAselectable');
							dojo.connect(node, 'click', (event) => {
								if (this.isCurrentPlayerActive())
								{
									if (stateName === 'selectCounters')
									{
										dojo.toggleClass(event.currentTarget, 'ERAselected');
										dojo.toggleClass('ERAselectButton', 'disabled', !this.checkGrowthActions());
									}
									else if (stateName === 'resolveGrowthActions')
									{
										switch (counter)
										{
											case 'research':
												const technologies = dojo.query('.ERAcounter-technology', 'ERAchoice').reduce((counters, node) => [...counters, node.getAttribute('counter')], []);
												this.action('research', {color: this.color, technologies: JSON.stringify(technologies)});
												break;
											case 'growPopulation':
												this.setClientState(counter, {counter: node.id, possibleactions: ['growPopulation'
													], descriptionmyturn: _('${you} may add one population disc to every star that is below its “growth limit”')});
												break;
											case 'gainStar':
												this.setClientState(counter, {counter: node.id, possibleactions: ['gainStar', 'declareWar'], descriptionmyturn: _('${you} may choose to populate or take over a star')});
												break;
											case 'buildShips':
												this.setClientState(counter, {counter: node.id, possibleactions: ['buildShips'], descriptionmyturn: _('${you} get new ships') + ` (${state.args._private.newShips})`});
												break;
										}
									}
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
				case 'remoteViewing':
					dojo.query('#ERAboard .ERAcounter-star:not([back]').addClass('ERAselectable').addClass('ERAselected');
					dojo.query('#ERAboard .ERAcounter-relic:not([back]').addClass('ERAselectable').addClass('ERAselected');
					dojo.query(`#ERAboard .ERAship[fleet]:not(.ERAship-${state.args.active})`).addClass('ERAselectable').addClass('ERAselected');
					dojo.query('#ERAboard .ERAdominationCard[domination="back"]').addClass('ERAselectable').addClass('ERAselected');
					break;
//
				case 'fleets':
					dojo.query(`#ERAboard .ERAship[color=${this.color}]`).addClass('ERAselectable');
					break;
//
				case 'movement':
					dojo.query(`#ERAboard .ERAship[color=${this.color}]`).addClass('ERAselectable');
					break;
//
				case 'combatChoice':
					{
						const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
						dojo.setStyle(svg, 'position', 'absolute');
						dojo.setStyle(svg, 'left', '0px');
						dojo.setStyle(svg, 'top', '0px');
						dojo.setStyle(svg, 'z-index', '150');
						dojo.setStyle(svg, 'pointer-events', 'all');
						svg.setAttribute("width", 10000);
						svg.setAttribute("height", 10000);
						svg.id = 'ERAcombatChoice';
//
						for (let location of state.args.combatChoice)
						{
							dojo.query(`[location='${location}']`, 'ERAboard').addClass('ERAselectable');
							svg.appendChild(this.board.drawHexagon(this.board.hexagons[location], "#" + state.args.active + 'C0'));
						}
//
						this.board.board.appendChild(svg);
					}
					break;
//
				case 'retreat':
					{
						const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
						dojo.setStyle(svg, 'position', 'absolute');
						dojo.setStyle(svg, 'left', '0px');
						dojo.setStyle(svg, 'top', '0px');
						dojo.setStyle(svg, 'z-index', '150');
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
				case 'resolveGrowthActions':
					dojo.query('.ERAprovisional,.ERAprovisionalBonus').remove().forEach((node) => this.counters.arrange(dojo.getAttr(node, 'location')));
					break;
//
				case 'growPopulation':
					{
						for (let [location, {population: population, growthLimit: growthLimit}] of Object.entries(state.args._private.growPopulation))
							if (population < growthLimit)
								dojo.query(`#ERAboard .ERAcounter-populationDisk.ERAcounter-${this.color}[location='${location}']`).addClass('ERAselectable').addClass('ERAselected');
					}
					break;
//
				case 'bonusPopulation':
					{
						dojo.query(`#ERAboard .ERAhomeStar[color='${this.color}']`).addClass('ERAselectable').addClass('ERAselected');
						for (let [location, {population: population, growthLimit: growthLimit}] of Object.entries(state.args._private.growPopulation))
							dojo.query(`#ERAboard .ERAcounter-populationDisk.ERAcounter-${this.color}[location='${location}']`).addClass('ERAselectable').addClass('ERAselected');
					}
					break;
//
				case 'buildShips':
					{
						dojo.query(`#ERAboard .ERAhomeStar[color='${this.color}']`).addClass('ERAselectable').addClass('ERAselected');
						for (let location of state.args._private.buildShips)
							dojo.query(`#ERAboard .ERAcounter-populationDisk.ERAcounter-${this.color}[location='${location}']`).addClass('ERAselectable').addClass('ERAselected');
					}
					break;
//
				case 'gainStar':
					{
						dojo.query(`#ERAboard .ERAship[color=${this.color}]`).addClass('ERAselectable');
						dojo.query(`.ERAcounter-peace`, `ERAstatus-${this.color}`).addClass('ERAselectable');
						for (let location in state.args._private.gainStar)
						{
							for (let star of state.args._private.gainStar[location])
							{
								dojo.addClass(`ERAcounter-${star}`, 'ERAselectable');
								dojo.addClass(`ERAcounter-${star}`, 'ERAselected');
							}
						}
					}
					break;
//
				case 'buildShips':
					break;
//
				case 'tradingPhase':
					{
						if (this.isCurrentPlayerActive())
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
									dojo.toggleClass(node, 'ERAselected', to in state.args._private.trade && from in state.args._private.trade[to] && state.args._private.trade[to][from].technology === technology)
								}
//
								const toColor = dojo.place(`<div style='display: flex;flex-direction: column;flex: 1 1 auto;align-items: center;' class='ERAtrade' id='ERAtradeTo-${from}-${to}'></div>`, container);
								dojo.place(`<div style='color:white;margin-bottom: 10px;'>${_('You are getting')}</div>`, toColor);
								for (let [technology, level] of Object.entries(state.args._private.inContact[to]))
								{
									const node = dojo.place(this.format_block('ERAcounter', {id: technology, color: to, type: 'technology', location: ''}), toColor);
									dojo.toggleClass(node, 'ERAhide', level <= state.args._private[technology]);
									dojo.toggleClass(node, 'ERAselectable', !dojo.hasClass(node, 'ERAdisabled'));
									dojo.toggleClass(node, 'ERAselected', from in state.args._private.trade && to in state.args._private.trade[from] && state.args._private.trade[from][to].technology === technology)
//
									dojo.connect(node, 'click', () => this.action('trade', {from: from, to: to, technology: technology}))
								}
								const node = dojo.place('<div style="display: flex;justify-content: space-between;"></div>', _container);
								const refuse = dojo.place(`<div class='bgabutton'>${_('Refuse trade')}</div>`, node);
								dojo.style(refuse, 'background', '#' + to + '80');
								dojo.style(refuse, 'pointer-events', 'all');
								dojo.connect(refuse, 'click', () => this.action('trade', {from: from, to: to, technology: 'refuse'}))
								const accept = dojo.place(`<div class='bgabutton'>${_('Accept trade')}</div>`, node);
								dojo.style(accept, 'background', '#' + to + '80');
								dojo.style(accept, 'pointer-events', 'all');
								if (from in state.args._private.trade && to in state.args._private.trade[from] && state.args._private.trade[from][to].pending) dojo.addClass(accept, 'ERAdisabled', );
								if (to in state.args._private.trade && from in state.args._private.trade[to] && state.args._private.trade[to][from].pending) dojo.addClass(accept, 'ERAdisabled', );
								dojo.connect(accept, 'click', () => this.action('trade', {from: from, to: to, technology: 'accept'}))
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
								this.myDlg = new ebg.popindialog();
								this.myDlg.create('trade');
								this.myDlg.setTitle(_('You must accept or refuse trading'));
								this.myDlg.setContent(html);
								this.myDlg.hideCloseIcon();
								this.myDlg.show();
								dojo.style('popin_trade_underlay', 'visibility', 'hidden');
							}
						}
					}
					break;
			}
		},
		onLeavingState: function (stateName)
		{
			console.log('Leaving state: ' + stateName);
//
			dojo.destroy('ERAchoice');
			dojo.destroy('ERApath');
			dojo.destroy('ERAcombatChoice');
//
			dojo.query('#ERAfleets').addClass('ERAhide');
			dojo.query('.ERAselected').removeClass('ERAselected');
			dojo.query('.ERAselectable').removeClass('ERAselectable');
//
			dojo.query('#popin_trade').remove();
		},
		onUpdateActionButtons: function (stateName, args)
		{
			console.log('onUpdateActionButtons: ' + stateName, args);
//
			if (this.isCurrentPlayerActive())
			{
				if (this.gamedatas.gamestate.possibleactions.includes('declareWar')) dojo.query(`.ERAcounter-peace[color='${this.color}']`, 'player_boards').addClass('ERAselectable');
				if (this.gamedatas.gamestate.possibleactions.includes('declarePeace')) dojo.query(`.ERAcounter-war[color='${this.color}']`, 'player_boards').addClass('ERAselectable');
//
				if (this.gamedatas.gamestate.possibleactions.includes('remoteViewing'))
				{
					this.addActionButton('ERAviewButton', '<span class="fa fa-eye fa-spin"></span> ×' + (args._private.view < 0 ? '∞' : args._private.view), () =>
					{
						if (stateName === 'remoteViewing') return this.restoreServerGameState();
						if (args._private.view !== 0) this.setClientState('remoteViewing', {descriptionmyturn: _('${you} may may secretly look at one “hidden thing”')});
					});
					dojo.setAttr('ERAviewButton', 'title', _('Remote Viewing is the psychic ability to tap into the Universal Mind to see any event anywhere in space and time'));
					if (args._private.view === 0) dojo.style('ERAviewButton', 'filter', 'grayscale(1)');
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
					case 'advancedFleetTactic':
//
						dojo.empty('ERAfleets');
						for (let [fleet, {location: location, ships: ships}] of Object.entries(args._private.fleets))
						{
							const _fleetNode = dojo.place(this.format_block('ERAfleet', {fleet: fleet, location: location, ships: ships}), 'ERAfleets');
//
							dojo.place(`<div class='ERAfleetAction' style="color:white;"></div>`, _fleetNode);
							const fleetNode = dojo.place(this.format_block('ERAship', {id: fleet, color: args._private.color, ship: ships, location: location}), _fleetNode);
							dojo.setAttr(fleetNode, 'fleet', fleet);
							dojo.toggleClass(fleetNode, 'ERAselectable', !args._private.advancedFleetTactic[fleet]);
//
							const shipsNode = dojo.place(`<div style='display:relative;width:50px;height:0px'></div>`, _fleetNode);
							for (let index = 0; index < ships; index++)
							{
								let node = dojo.place(this.format_block('ERAship', {id: fleet, color: args._private.color, location: location}), shipsNode);
								dojo.style(node, 'transform', `scale(20%) translateY(${index * node.clientHeight / 4}px)`);
								dojo.style(node, 'transform-origin', 'left top');
							}
//
							dojo.connect(fleetNode, 'click', (event) => {
								dojo.stopEvent(event);
								if (dojo.hasClass(event.currentTarget, 'ERAselected')) {
									dojo.query('.ERAcounter-tactic', 'generalactions').addClass('ERAdisabled');
									dojo.removeClass(event.currentTarget, 'ERAselected');
								}
								else
								{
									dojo.query('.ERAcounter-tactic', 'generalactions').removeClass('ERAdisabled');
									dojo.query('.ERAship[fleet].ERAselectable', 'ERAfleets').removeClass('ERAselected');
									dojo.addClass(event.currentTarget, 'ERAselected');
								}
							}
							);
						}
						dojo.removeClass('ERAfleets', 'ERAhide');
//
						const node2x = dojo.place(this.format_block('ERAcounter', {id: '2x', color: args._private.color, type: 'tactic', location: ''}), 'generalactions');
						dojo.setAttr(node2x, 'tactic', '2x');
						const nodeDP = dojo.place(this.format_block('ERAcounter', {id: 'DP', color: args._private.color, type: 'tactic', location: ''}), 'generalactions');
						dojo.setAttr(nodeDP, 'tactic', 'DP');
//
						dojo.query('.ERAcounter-tactic', 'generalactions').addClass('ERAselectable ERAdisabled').connect('click', (event) => {
							const fleets = dojo.query('.ERAship[fleet].ERAselected', 'ERAfleets');
							if (fleets.length === 1) this.action('advancedFleetTactic', {color: args._private.color, fleet: dojo.getAttr(fleets[0], 'fleet'), tactic: event.currentTarget.getAttribute('tactic')});
						});
						break;
//
					case 'fleets':
//
						dojo.empty('ERAfleets');
						for (let [fleet, {location: location, ships: ships}] of Object.entries(args._private.fleets))
						{
							const _fleetNode = dojo.place(this.format_block('ERAfleet', {fleet: fleet, location: location, ships: ships}), 'ERAfleets');
//
							dojo.place(`<div class='ERAfleetAction' style="color:white;"></div>`, _fleetNode);
							const fleetNode = dojo.place(this.format_block('ERAship', {id: fleet, color: this.color, ship: ships, location: location}), _fleetNode);
							dojo.setAttr(fleetNode, 'fleet', fleet);
//
							const shipsNode = dojo.place(`<div style='display:relative;width:50px;height:0px'></div>`, _fleetNode);
							for (let index = 0; index < ships; index++)
							{
								let node = dojo.place(this.format_block('ERAship', {id: fleet, color: this.color, location: location}), shipsNode);
								dojo.style(node, 'transform', `scale(20%) translateY(${index * node.clientHeight / 4}px)`);
								dojo.style(node, 'transform-origin', 'left top');
								dojo.addClass(node, 'ERAselectable');
								dojo.connect(node, 'click', (event) => {
									dojo.stopEvent(event);
									if (event.detail === 1) dojo.toggleClass(node, 'ERAselected');
									if (event.detail === 2) dojo.query(`#ERAfleets>.ERAfleet[fleet='${fleet}'] .ERAship:not([fleet]).ERAselectable`).toggleClass('ERAselected', dojo.hasClass(node, 'ERAselected'));
								});
							}
//
//							dojo.style(fleetNode, 'pointer-events', 'all');
							dojo.connect(fleetNode, 'click', (event) => {
								dojo.stopEvent(event);
								const fleet = dojo.getAttr(event.currentTarget, 'fleet');
								const shipsToFleet = dojo.query(`#ERAboard .ERAship.ERAselected:not([fleet])`).reduce((L, node) => [...L, +node.getAttribute('ship')], []);
								if (shipsToFleet.length)
								{
									dojo.empty('ERAfleets');
									return this.action('shipsToFleet', {color: this.color, fleet: fleet, ships: JSON.stringify(shipsToFleet)});
								}
								const fleetToShips = dojo.query(`#ERAfleets .ERAship.ERAselected[ship='${fleet}']:not([fleet])`).reduce((L, node) => [...L, +node.getAttribute('ship')], []);
								if (fleetToShips.length)
								{
									dojo.empty('ERAfleets');
									return this.action('fleetToShips', {color: this.color, fleet: fleet, ships: fleetToShips.length});
								}
								const fleetToFleet = dojo.query(`#ERAfleets .ERAship.ERAselected:not([ship='${fleet}']):not([fleet])`);
								if (fleetToFleet.length)
								{
									dojo.empty('ERAfleets');
									return this.action('fleetToFleet', {color: this.color, from: dojo.getAttr(fleetToFleet[0], 'ship'), to: fleet, ships: fleetToFleet.length});
								}
								const fleets = dojo.query(`#ERAboard .ERAship.ERAselected[fleet]`).reduce((L, node) => [...L, node.getAttribute('fleet')], [fleet]);
								dojo.empty('ERAfleets');
								this.action('swapFleets', {color: this.color, fleets: JSON.stringify(fleets)});
							}
							);
						}
						this.addActionButton('ERAdoneButton', _('Go to Movement phase'), () => {
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
						this.addActionButton('ERAundoButton', _('undo'), () => this.action('undo', {color: this.color}));
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
//
						if (!args.winner) this.addActionButton('ERAnoRetreat', _('No retreat'), () => this.action('retreat', {color: this.color, location: '""'}));
						break;
//
					case 'battleLoss':
//
						this.addActionButton('ERAreset', _('Reset'), () => this.restoreServerGameState());
						this.addActionButton('ERAdone', _('Done'), () => {
							let ships = {winner: [], losers: []};
							dojo.query('.ERAship:not([fleet]).ERAselected', 'ERAwinner').forEach((node) => ships.winner.push([node.getAttribute('color'), node.getAttribute('location')]));
							dojo.query('.ERAship:not([fleet]).ERAselected', 'ERAlosers').forEach((node) => ships.losers.push([node.getAttribute('color'), node.getAttribute('location')]));
							this.action('battleLoss', {color: this.color, ships: JSON.stringify(ships)});
						});
//
						if (args.totalVictory) dojo.place(`<span style='font-size:small;'><BR>${_('No ships are destroyed from the winning side')}</span>`, 'generalactions');
						else dojo.place(`<span style='font-size:small;'><BR><span id='ERAtoDestroy'></span></span>`, 'generalactions');
//
						dojo.place(this.format_block('ERAchoice', {}), 'game_play_area');
//
						for (let side of ['winner', 'losers'])
						{
							const sideNode = dojo.place(`<div id='ERA${side}' style='flex:1 1 50%;display:flex;flex-direction:column;background:#000000C0;margin:25px;border-radius:25px'></div>`, 'ERAchoice');
							dojo.place(`<div style='margin:5% 0% 5% 5%;color:white;'><span style='font-weight:bold;'>${{winner: _('Winner side'), losers: _('Loser side')}[side]}&nbsp:&nbsp</span><span id='ERA${side}Lose'></span>&nbsp${_('ship(s) destroyed')}</div>`, sideNode);
//
							for (let color in args[side])
							{
								for (let [fleet, ships] of Object.entries(args[side][color]))
								{
									const fleetNode = dojo.place(this.format_block('ERAfleetH', {fleet: fleet, ships: ships}), sideNode);
//
									if (fleet !== 'ships')
									{
										const node = dojo.place(this.format_block('ERAship', {id: fleet, color: color, ship: ships, location: ''}), fleetNode);
										dojo.setAttr(node, 'fleet', fleet);
										dojo.style(node, 'position', 'relative');
										dojo.addClass(node, 'ERAselectable');
										dojo.connect(node, 'click', (event) => {
											dojo.stopEvent(event);
											dojo.toggleClass(event.currentTarget, 'ERAselected');
											dojo.query(`#ERA${side}>.ERAfleet[fleet='${fleet}'] .ERAship:not([fleet]).ERAselectable`).toggleClass('ERAselected', dojo.hasClass(event.currentTarget, 'ERAselected'));
											this.battleLoss(args.totalVictory);
										});
										dojo.addClass(node, 'ERAselectable');
									}
//
									for (let index = 0; index < ships; index++)
									{
										const shipNode = dojo.place(`<div style='width:50px;height:50px;transform:scale(20%);transform-origin:left top;margin-right:-25px'></div>`, fleetNode);
										const node = dojo.place(this.format_block('ERAship', {id: 'ship', color: color, location: fleet}), shipNode);
										dojo.connect(node, 'click', (event) => {
											dojo.stopEvent(event);
											if (event.detail === 1) dojo.toggleClass(event.currentTarget, 'ERAselected');
											if (event.detail === 2)
												dojo.query(`#ERA${side}>.ERAfleet[fleet='${fleet}'] .ERAship:not([fleet]).ERAselectable`).toggleClass('ERAselected', dojo.hasClass(event.currentTarget, 'ERAselected'));
											this.battleLoss(args.totalVictory);
										});
										dojo.addClass(node, 'ERAselectable');
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
						this.addActionButton('ERAcancelButton', _('Cancel'), () => this.restoreServerGameState());
						this.addActionButton('ERAselectButton', dojo.string.substitute(_('Select Growth Actions (${oval})'), {oval: args._private.oval}), () =>
						{
							const counters = dojo.query(`#ERAchoice .ERAselected`).reduce((L, node) => [...L, node.getAttribute('counter')], []);
							if (counters.includes('research') && counters.filter(action => ['Military', 'Spirituality', 'Propulsion', 'Robotics', 'Genetics'].includes(action)).length === 0)
							{
								this.confirmationDialog(_('Research growth action selected without technology counter(s)'), () => {
									this.action('selectCounters', {color: args._private.color, counters: JSON.stringify(counters)}, () => {
										if (this.gamedatas.gamestate.name === 'selectCounters')
										{
											this.last_server_state.args._private.counters = counters;
											this.restoreServerGameState();
										}
									});
								});
							}
							else if (counters.filter(action => ['Military', 'Spirituality', 'Propulsion', 'Robotics', 'Genetics', 'changeTurnOrderUp', 'changeTurnOrderDown'
								].includes(action)).length < args._private.square)
							{
								this.confirmationDialog(_('You may select 2 square counters'), () => {
									this.action('selectCounters', {color: args._private.color, counters: JSON.stringify(counters)}, () => {
										if (this.gamedatas.gamestate.name === 'selectCounters')
										{
											this.last_server_state.args._private.counters = counters;
											this.restoreServerGameState();
										}
									});
								});
							}
							else
							{
								this.action('selectCounters', {color: args._private.color, counters: JSON.stringify(counters)}, () => {
									if (this.gamedatas.gamestate.name === 'selectCounters')
									{
										this.last_server_state.args._private.counters = counters;
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
								dojo.connect(dojo.place(`<span index='${i}' class=' ERAadditionalAction action-button bgabutton bgabutton_small bgabutton_blue'>${args._private.additionalOvalCost} DP</span>`, node), 'click', (event) => {
									dojo.toggleClass(event.currentTarget, 'bgabutton_blue bgabutton_red');
									args._private.oval = this.last_server_state.args._private.oval + dojo.query('.ERAadditionalAction.bgabutton_red').length;
									$('ERAselectButton').innerHTML = dojo.string.substitute(_('Select Growth Actions (${oval})'), {oval: args._private.oval});
									dojo.toggleClass('ERAselectButton', 'disabled', !this.checkGrowthActions());
								});
							}
						}
						break;
//
					case 'resolveGrowthActions':
//
						this.addActionButton('ERApassButton', _('End turn'), () => this.action('pass', {color: this.color}), null, false, 'red');
						break;
//
					case 'research':
//
						this.addActionButton('ERAcancelButton', _('Cancel'), () => this.restoreServerGameState());
						break;
//
					case 'buildShips':
//
						this.addActionButton('ERAbuildShipsButton', _('Confirm'), () => {
							const locations = dojo.query('.ERAprovisional').reduce((L, node) => [...L, node.getAttribute('location')], []);
							this.action('buildShips', {color: this.color, locations: JSON.stringify(locations)});
							dojo.query('.ERAprovisional').remove();
						}, null, false, 'red');
						this.addActionButton('ERAcancelButton', _('Cancel'), () => this.restoreServerGameState());
						break;
//
					case 'growPopulation':
//
						this.addActionButton('ERAgrowPopulationButton', _('Confirm'), () => {
							this.setClientState('bonusPopulation', {descriptionmyturn: dojo.string.substitute(_('${you} may add ${bonus} “bonus population” discs'), {you: '${you}', bonus: args._private.bonusPopulation})});
						});
						this.addActionButton('ERAcancelButton', _('Cancel'), () => this.restoreServerGameState());
						break;
//
					case 'bonusPopulation':
//
						this.addActionButton('ERAbonusPopulationButton', _('Confirm'), () => {
							const locations = dojo.query('.ERAprovisional').reduce((L, node) => [...L, node.getAttribute('location')], []);
							const locationsBonus = dojo.query('.ERAprovisionalBonus').reduce((L, node) => [...L, node.getAttribute('location')], []);
							this.action('growPopulation', {color: this.color, locations: JSON.stringify(locations), locationsBonus: JSON.stringify(locationsBonus)});
							dojo.query('.ERAprovisional,.ERAprovisionalBonus').remove().forEach((node) => this.counters.arrange(dojo.getAttr(node, 'location')));
						}, null, false, 'red');
						this.addActionButton('ERAcancelButton', _('Cancel'), () => this.restoreServerGameState());
						break;
//
					case 'gainStar':
//
						this.addActionButton('ERAcancelButton', _('Cancel'), () => this.restoreServerGameState());
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
			dojo.subscribe('update_score', (notif) => this.scoreCtrl[notif.args.player_id].setValue(notif.args.score));
			dojo.subscribe('updateRound', (notif) => this.updateRound(notif.args.round));
			dojo.subscribe('updateFaction', (notif) => this.factions.update(notif.args.faction));
//
			dojo.subscribe('placeCounter', (notif) => this.counters.place(notif.args.counter));
			dojo.subscribe('flipCounter', (notif) => this.counters.flip(notif.args.counter));
			dojo.subscribe('removeCounter', (notif) => this.counters.remove(notif.args.counter));
//
			dojo.subscribe('placeShip', (notif) => this.ships.place(notif.args.ship));
			dojo.subscribe('moveShips', (notif) => this.ships.move(notif.args.ships, notif.args.location));
			dojo.subscribe('revealShip', (notif) => this.ships.reveal(notif.args.ship));
			dojo.subscribe('removeShip', (notif) => this.ships.remove(notif.args.ship));
//
			this.setSynchronous();
		},
		setSynchronous()
		{
			this.notifqueue.setSynchronous('updateRound', DELAY);
			this.notifqueue.setSynchronous('updateFaction', DELAY / 2);
//
			this.notifqueue.setSynchronous('placeCounter', DELAY);
			this.notifqueue.setSynchronous('flipCounter', DELAY);
			this.notifqueue.setSynchronous('removeCounter', DELAY);
//
			this.notifqueue.setSynchronous('placeShip', DELAY);
			this.notifqueue.setSynchronous('moveShips', DELAY / 2);
			this.notifqueue.setSynchronous('revealShip', DELAY / 2);
			this.notifqueue.setSynchronous('removeShip', DELAY);
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
		remoteViewing: function (type, node)
		{
			switch (type)
			{
				case 'counter':
					return this.action('remoteViewing', {color: this.color, type: 'counter', id: dojo.getAttr(node, 'counter')});
				case 'fleet':
					return this.action('remoteViewing', {color: this.color, type: 'fleet', id: dojo.getAttr(node, 'ship')});
				case 'dominationCard':
					return this.action('remoteViewing', {color: this.color, type: 'dominationCard', id: dojo.getAttr(node, 'owner') + '_' + dojo.getAttr(node, 'index')});
			}

		},
		fleets: function (location, type, nodes)
		{
			console.log(location, type, nodes.length);
//
			dojo.toggleClass('ERAfleets', 'ERAhide', nodes.length === 0);
			dojo.query('.ERAship', 'ERAfleets').removeClass('ERAselectable ERAselected');
			if (nodes.length === 0) return;
//
			switch (type)
			{
				case 'ships':
					dojo.query(`#ERAfleets .ERAship`).removeClass('ERAselectable');
//
					dojo.query('#ERAfleets .ERAfleet').forEach((node) => {
						let hide = true;
						let fleetLocation = dojo.getAttr(node, 'location');
						if ((fleetLocation === 'stock' && this.gamedatas.gamestate.args._private.stars.includes(location)) || fleetLocation === location)
						{
							hide = false;
							node.querySelector('.ERAfleetAction').innerHTML = '⟱';
							dojo.query('.ERAship', node).addClass('ERAselectable');
						}
						dojo.toggleClass(node, 'ERAhide', hide);
					});
					break;
				case 'fleet':
					const fleet = dojo.getAttr(nodes[0], 'fleet');
					dojo.query('#ERAfleets .ERAfleet').forEach((node) => {
						let hide = true;
						if (fleet !== dojo.getAttr(node, 'fleet'))
						{
							if (this.gamedatas.gamestate.args._private.stars.includes(location))
							{
								let fleetLocation = dojo.getAttr(node, 'location');
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
		combatChoice: function (location)
		{
			if (this.gamedatas.gamestate.args.combatChoice.includes(location)) this.action('combatChoice', {color: this.color, location: JSON.stringify(location)});
		},
		retreat: function (location)
		{
			if (this.gamedatas.gamestate.args.retreat.includes(location)) this.action('retreat', {color: this.color, location: JSON.stringify(location)});
		},
		gainStar: function (location)
		{
			if (location in this.gamedatas.gamestate.args._private.gainStar) this.action('gainStar', {color: this.color, location: JSON.stringify(location)});
		},
		buildShips: function (location)
		{
			if (this.gamedatas.gamestate.args._private.buildShips.includes(location))
			{
				if (dojo.query(`.ERAship.ERAprovisional`).length < this.gamedatas.gamestate.args._private.newShips)
				{
					const ship = this.ships.place({id: 'buildShips', color: this.color, fleet: 'ship', location: location});
					dojo.addClass(ship, 'ERAprovisional');
				}
			}
		},
		growPopulation: function (location)
		{
			if (location in this.gamedatas.gamestate.args._private.growPopulation)
			{
				let nodes = dojo.query(`.ERAcounter-populationDisk[location='${location}'].ERAprovisional`);
				if (nodes.length)
				{
					nodes.remove();
					this.counters.arrange(location);
				}
				else
				{
					const population = +this.gamedatas.gamestate.args._private.growPopulation[location].population;
					const limit = +this.gamedatas.gamestate.args._private.growPopulation[location].growthLimit;
					if (population < limit) dojo.addClass(this.counters.place({id: 'growPopulation', color: this.color, type: 'populationDisk', location: location}), 'ERAprovisional');
				}
			}
		},
		bonusPopulation: function (location)
		{
			if (location in this.gamedatas.gamestate.args._private.growPopulation)
			{
				let nodes = dojo.query(`.ERAcounter-populationDisk[location='${location}'].ERAprovisionalBonus`);
				if (nodes.length)
				{
					nodes.remove();
					this.counters.arrange(location);
				}
				else if (dojo.query(`.ERAcounter-populationDisk.ERAprovisionalBonus`).length < this.gamedatas.gamestate.args._private.bonusPopulation)
					dojo.addClass(this.counters.place({id: 'growPopulation', color: this.color, type: 'populationDisk', location: location}), 'ERAprovisionalBonus');
			}
		},
		battleLoss: function (totalVictory)
		{
			const winnerLose = dojo.query('.ERAship:not([fleet]).ERAselected', 'ERAwinner').length;
			const losersLose = dojo.query('.ERAship:not([fleet]).ERAselected', 'ERAlosers').length;
			const toDestroy = totalVictory ? 0 : Math.ceil(losersLose / 2);
//
			$('ERAwinnerLose').innerHTML = winnerLose;
			$('ERAlosersLose').innerHTML = losersLose;
			if (!totalVictory) $('ERAtoDestroy').innerHTML = dojo.string.substitute(_('You must destroy ${N} of your ships'), {N: toDestroy});
//
			dojo.toggleClass('ERAdone', 'disabled', winnerLose !== toDestroy);
		},
		checkGrowthActions: function ()
		{
			const oval = dojo.query('#ERAchoice .ERAcounter-growth.ERAselected').length === +this.gamedatas.gamestate.args._private.oval;
			const square = dojo.query('#ERAchoice .ERAcounter-technology.ERAselected,#ERAchoice .ERAcounter-turnOrder.ERAselected').length >= 1;
//
			return oval && square;
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
				if ('GPS' in args) {
					this.board.centerMap(args.GPS);
					args.GPS = `<span onclick="gameui.onCenter(event)" location='${args.GPS}'>📌</span>`;
				}
				if ('DICE' in args) args.DICE = `<span class='ERAdice' style='background-position-x:-${30 * (args.DICE - 1)}px'></span>`;
				if ('DICE1' in args) args.DICE1 = `<span class='ERAdice' style='background-position-x:-${30 * (args.DICE1 - 1)}px'></span>`;
				if ('DICE2' in args) args.DICE2 = `<span class='ERAdice' style='background-position-x:-${30 * (args.DICE2 - 1)}px'></span>`;
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
			if (this.checkAction(action))
			{
				args.lock = true;
				this.ajaxcall(`/galacticera/galacticera/${action}.html`, args, this, success, fail);
		}
		}
	}
	);
}
);
