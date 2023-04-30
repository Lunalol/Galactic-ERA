define(["dojo", "dojo/_base/declare", "ebg/core/gamegui", "ebg/counter",
	g_gamethemeurl + "modules/JavaScript/hexagons.js",
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
//
			this.players = {};
			for (let faction of Object.values(gamedatas.factions)) this.players[faction.player_id] = faction.color;
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
//
// Setup player panels
//
				let nodeFaction = dojo.place(`<div class='ERAfaction' id='ERAfaction-${faction.color}'></div>`, `player_board_${faction.player_id}`, 2);
//				dojo.connect(nodeFaction, 'click', () => {
//					const focus = dojo.hasClass(nodeFaction, 'ERAfocus');
//					dojo.query('.ERAfocus').removeClass('ERAfocus');
//					if (!focus) dojo.addClass(nodeFaction, 'ERAfocus');
//				});
//
				let nodeStarPeople = dojo.place(this.format_block('ERAstarPeople', {starpeople: faction.starPeople}), nodeFaction);
//				let nodeOrder = dojo.place(`<div class='ERAcounter ERAorder' id='ERAorder-${faction.color}'></div>`, nodeStarPeople, 'after');
//				dojo.connect(nodeOrder, 'click', () => this.board.centerMap(faction.homeStar + ':+0+0+0'));
				this.factions.update(faction);
			}
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
				}
			}
//
			this.setupNotifications();
//
			console.log("Ending game setup");
		},
		onEnteringState: function (stateName, state)
		{
			console.log('Entering state: ' + stateName, state);
//
			if (!(state.args)) return;
			this.color = ('active' in state.args) ? state.args.active : undefined;
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
				let research = false;
				let ID = 0;
				for (const counter of state.args._private.counters)
				{
					ID += 1;
					switch (counter)
					{
						case 'Military':
						case 'Spirituality':
						case 'Propulsion':
						case 'Robotics':
						case 'Genetics':
							{
								const container = dojo.place('<div></div>', technologyNode);
								const node = dojo.place(this.format_block('ERAcounter', {id: 'counters-' + ID, color: state.args._private.color, type: 'technology', location: ''}), container);
								dojo.setAttr(node, 'counter', counter);
								dojo.style(node, 'pointer-events', 'all');
								dojo.connect(node, 'click', (event) => {
									if (this.isCurrentPlayerActive())
									{
										if (stateName === 'selectCounters')
										{
											dojo.query('#ERAchoice .ERAcounter-turnOrder.ERAselected').removeClass('ERAselected');
											dojo.query('#ERAchoice .ERAcounter-technology.ERAselected').removeClass('ERAselected');
											dojo.toggleClass(event.currentTarget, 'ERAselected');
											dojo.toggleClass('ERAselectButton', 'disabled', dojo.query('#ERAchoice .ERAcounter-growth.ERAselected').length !== +state.args._private.N || dojo.query('#ERAchoice .ERAcounter-technology.ERAselected,#ERAchoice .ERAcounter-turnOrder.ERAselected').length !== 1);
										}
										if (stateName === 'research')
										{
											dojo.style(node, 'pointer-events', 'all');
											dojo.toggleClass(event.currentTarget, 'ERAselected');
											this.action('research', {color: this.color, technology: counter});
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
								const node = dojo.place(this.format_block('ERAcounter', {id: 'counters-' + ID, color: state.args._private.color, type: 'turnOrder', subtype: counter, location: ''}), container);
								dojo.setAttr(node, 'counter', counter);
								dojo.style(node, 'pointer-events', 'all');
								dojo.connect(node, 'click', (event) => {
									if (this.isCurrentPlayerActive())
									{
										dojo.query('#ERAchoice .ERAcounter-turnOrder.ERAselected').removeClass('ERAselected');
										dojo.query('#ERAchoice .ERAcounter-technology.ERAselected').removeClass('ERAselected');
										dojo.toggleClass(event.currentTarget, 'ERAselected');
										dojo.toggleClass('ERAselectButton', 'disabled', dojo.query('#ERAchoice .ERAcounter-growth.ERAselected').length !== +state.args._private.N || dojo.query('#ERAchoice .ERAcounter-technology.ERAselected,#ERAchoice .ERAcounter-turnOrder.ERAselected').length !== 1);
									}
								});
							}
							break;
						default:
						{
							const container = dojo.place("<div></div>", growthNode);
							const node = dojo.place(this.format_block('ERAcounter', {id: 'counters-' + ID, color: state.args._private.color, type: 'growth', subtype: counter, location: ''}), container);
							dojo.setAttr(node, 'counter', counter);
							dojo.style(node, 'pointer-events', 'all');
							dojo.connect(node, 'click', (event) => {
								if (this.isCurrentPlayerActive())
								{
									if (stateName === 'selectCounters')
									{
										dojo.toggleClass(event.currentTarget, 'ERAselected');
										dojo.toggleClass('ERAselectButton', 'disabled', dojo.query('#ERAchoice .ERAcounter-growth.ERAselected').length !== +state.args._private.N || dojo.query('#ERAchoice .ERAcounter-technology.ERAselected,#ERAchoice .ERAcounter-turnOrder.ERAselected').length !== 1);
									}
									else if (stateName === 'resolveGrowthActions')
									{
										switch (counter)
										{
											case 'research':
												this.setClientState(counter, {counter: node.id, possibleactions: ['research'], descriptionmyturn: _('${you} can research')});
												break;
											case 'growPopulation':
												this.setClientState(counter, {counter: node.id, possibleactions: ['growPopulation'
													], descriptionmyturn: _('${you} may add one population disc to every star that is below its “growth limit”')});
												break;
											case 'gainStar':
												this.setClientState(counter, {counter: node.id, possibleactions: ['gainStar'], descriptionmyturn: _('${you} may choose to populate or take over a star')});
												break;
											case 'buildShips':
												this.setClientState(counter, {counter: node.id, possibleactions: ['buildShips'], descriptionmyturn: _('${you} gets new ships')});
												break;
										}
									}
								}
							});
							if (counter === 'research') research = true;
						}
					}
				}
				if (stateName === 'individualChoice') dojo.query('#ERAchoice .ERAcounter-technology').addClass('ERAselectable');
				else dojo.query('#ERAchoice .ERAcounter-technology').style('filter', research ? '' : 'grayscale(1)');
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
				case 'movement':
				{
					if (this.isCurrentPlayerActive()) dojo.query(`#ERAboard .ERAship[color=${this.color}]`).addClass('ERAselectable');
					break;
				}
				case 'research':
					{
					}
					break;
				case 'growPopulation':
					{
//						dojo.query(`#ERAboard .ERAhomeStar[color='${this.color}']`).addClass('ERAselectable').addClass('ERAselected');
						for (let [location, population] of Object.entries(state.args._private.growPopulation))
							dojo.query(`#ERAboard .ERAcounter-populationDisk.ERAcounter-${this.color}[location='${location}']`).addClass('ERAselectable').addClass('ERAselected');
					}
					break;
				case 'bonusPopulation':
					{
						dojo.query(`#ERAboard .ERAhomeStar[color='${this.color}']`).addClass('ERAselectable').addClass('ERAselected');
						for (let [location, population] of Object.entries(state.args._private.bonusPopulation))
							dojo.query(`#ERAboard .ERAcounter-populationDisk.ERAcounter-${this.color}[location='${location}']`).addClass('ERAselectable').addClass('ERAselected');
					}
					break;
				case 'buildShips':
					{
						dojo.query(`#ERAboard .ERAhomeStar[color='${this.color}']`).addClass('ERAselectable').addClass('ERAselected');
						for (let location of state.args._private.buildShips)
							dojo.query(`#ERAboard .ERAcounter-populationDisk.ERAcounter-${this.color}[location='${location}']`).addClass('ERAselectable').addClass('ERAselected');
					}
					break;
				case 'gainStar':
					{
						for (let stars of state.args._private.gainStar)
						{
							for (let star of stars)
							{
								dojo.addClass(`ERAcounter-${star}`, 'ERAselectable');
								dojo.addClass(`ERAcounter-${star}`, 'ERAselected');
							}
						}
					}
					break;
				case 'buildShips':
					{
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
//
			dojo.query('.ERAselected').removeClass('ERAselected');
			dojo.query('.ERAselectable').removeClass('ERAselectable');
//
			dojo.query('.ERAprovisional').remove();
		}
		,
		onUpdateActionButtons: function (stateName, args)
		{
			console.log('onUpdateActionButtons: ' + stateName);
//
			if (this.isCurrentPlayerActive())
			{
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
					case 'movement':
//
						this.addActionButton('ERAundoButton', _('undo'), () => this.action('undo', {color: this.color}));
//
						this.addActionButton('ERAscoutButton', _('Scout'), () => {
							let ships = dojo.query(`#ERAboard .ERAship.ERAselected`).reduce((L, node) => [...L, +node.getAttribute('ship')], []);
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
						this.addActionButton('ERAviewButton', '<span class="fa fa-eye fa-spin"></span>', () => this.action('view', {color: this.color}));
						dojo.setAttr('ERAviewButton', 'title', _('Remote Viewing is the psychic ability to tap into the Universal Mind to see any event anywhere in space and time'));
//
						this.addActionButton('ERApassButton', _('End turn'), () => {
							let node = $('ERApassButton');
							if (node.count)
							{
								delete node.count;
								return this.restoreServerGameState();
							}
							dojo.query('#generalcounters .bgabutton:not(#ERApassButton)').remove();
							node.count = 1;
							let timer = window.setInterval(() => {
								node.count -= 1;
								node.innerHTML = _('Cancel') + ` (${(+node.count / 10).toFixed(1)}s)`;
								if (node.count < 0)
								{
									window.clearInterval(timer);
									return this.action('pass', {color: this.color});
								}
							}, 100);
						}, null, false, 'red');
						break;
//
					case 'selectCounters':
//
						this.addActionButton('ERAselectButton', dojo.string.substitute(_('Select Growth Actions (${N})'), {N: args._private.N}), () =>
						{
							const counters = dojo.query(`#ERAchoice .ERAselected`).reduce((L, node) => [...L, node.getAttribute('counter')], []);
							this.action('selectCounters', {color: args._private.color, counters: JSON.stringify(counters)}, () => {
								this.last_server_state.args._private.counters = counters;
								this.restoreServerGameState();
							});
						});
						dojo.toggleClass('ERAselectButton', 'disabled', dojo.query('#ERAchoice .ERAcounter-growth.ERAselected').length !== +args._private.N || dojo.query('#ERAchoice .ERAcounter-technology.ERAselected,#ERAchoice .ERAcounter-turnOrder.ERAselected').length !== 1);
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
							const locations = dojo.query('.ERAprovisional').reduce((L, node) => [...L, node.getAttribute('location')], []);
							this.action('growPopulation', {color: this.color, locations: JSON.stringify(locations)});
							dojo.query('.ERAprovisional').remove();
						}, null, false, 'red');
						this.addActionButton('ERAcancelButton', _('Cancel'), () => this.restoreServerGameState());
						break;
//
					case 'bonusPopulation':
//
						this.addActionButton('ERAbonusPopulationButton', _('Confirm'), () => {
							const locations = dojo.query('.ERAprovisional').reduce((L, node) => [...L, node.getAttribute('location')], []);
							this.action('bonusPopulation', {color: this.color, locations: JSON.stringify(locations)});
							dojo.query('.ERAprovisional').remove();
						}, null, false, 'red');
						this.addActionButton('ERAcancelButton', _('Cancel'), () => this.restoreServerGameState());
						break;
//
					case 'gainStar':
//
						this.addActionButton('ERAcancelButton', _('Cancel'), () => this.restoreServerGameState());
						break;
//
				}
			}
		},
		setupNotifications: function ()
		{
			console.log('notifications subscriptions setup');
//
			dojo.subscribe('updateRound', (notif) => this.updateRound(notif.args.round));
			dojo.subscribe('updateFaction', (notif) => this.factions.update(notif.args.faction));
			dojo.subscribe('placeCounter', (notif) => this.counters.place(notif.args.counter));
			dojo.subscribe('flipCounter', (notif) => this.counters.flip(notif.args.counter));
			dojo.subscribe('removeCounter', (notif) => this.counters.remove(notif.args.counter));
			dojo.subscribe('placeShip', (notif) => this.ships.place(notif.args.ship));
			dojo.subscribe('moveShips', (notif) => this.ships.move(notif.args.ships, notif.args.location));
			dojo.subscribe('removeShip', (notif) => this.ships.remove(notif.args.ship));
//
			this.notifqueue.setSynchronous('placeShip', DELAY);
			this.notifqueue.setSynchronous('moveShips', DELAY / 2);
			this.notifqueue.setSynchronous('removeShip', DELAY);
			this.notifqueue.setSynchronous('placeCounter', DELAY);
			this.notifqueue.setSynchronous('flipCounter', DELAY);
			this.notifqueue.setSynchronous('removeCounter', DELAY);
		},
		updateRound: function (round)
		{
			if (round > 0)
			{
				dojo.style('ERApawn', 'left', (84.7 * round) + 'px');
				dojo.setAttr('ERApawn', 'title', dojo.string.substitute(_('Round ${round} of 8'), {round: round}));
			}

		},
		gainStar: function (location)
		{
			this.action('gainStar', {color: this.color, location: JSON.stringify(location)});
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
			if (location in this.gamedatas.gamestate.args._private.bonusPopulation)
			{
				let nodes = dojo.query(`.ERAcounter-populationDisk[location='${location}'].ERAprovisional`);
				if (nodes.length)
				{
					nodes.remove();
					this.counters.arrange(location);
				}
				else if (dojo.query(`.ERAcounter-populationDisk.ERAprovisional`).length < this.gamedatas.gamestate.args.bonus)
					dojo.addClass(this.counters.place({id: 'growPopulation', color: this.color, type: 'populationDisk', location: location}), 'ERAprovisional');
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
