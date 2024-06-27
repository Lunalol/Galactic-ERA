/* global g_gamethemeurl, ebg */

define(["dojo", "dojo/_base/declare", "dijit"], function (dojo, declare, dijit)
{
	return declare("Factions", null,
	{
		constructor: function (bgagame)
		{
			console.log('factions constructor');
//
// Reference to BGA game
//
			this.bgagame = bgagame;
//
// Setup DP
//
			const galacticStoryNode = dojo.place(`<img id='ERAgalacticStory' src='${g_gamethemeurl}img/galacticStories/${this.bgagame.gamedatas.galacticStory}.png' draggable='false'>`, 'ERA-DP');
			const galacticStory = bgagame.GALACTIC_STORIES[this.bgagame.gamedatas.galacticStory];
//
			let html = `<H1 style='font-family:ERA;'>${_('Galactic') + ' ' + _(this.bgagame.gamedatas.galacticStory)}</H1>`;
			html += '<div style="display:grid;grid-template-columns:1fr 5fr;max-width:50vw;outline:1px solid white;">';
			for (let [ERA, string] of Object.entries({1: _('First Era'), 2: _('Second Era'), 3: _('Third Era')}))
			{
				html += '<div style="padding:12px;text-align:center;outline:1px solid grey;">' + (string) + '</div>';
				html += '<div style="padding:12px;outline:1px solid grey;">';
				for (let string of galacticStory[ERA]) html += '<div style="text-align:justify;">◇&#x2005;' + string + '</div>';
				html += '</div>';
			}
//
			this.story = new dijit.Tooltip({connectId: galacticStoryNode, showDelay: 500, hideDelay: 0, label: html, position: ['above']});
//
			if (this.bgagame.gamedatas.galacticGoal !== 'None')
			{
				const galacticGoalNode = dojo.place(`<img id='ERAgalacticGoal' src='${g_gamethemeurl}img/galacticGoals/${this.bgagame.gamedatas.galacticGoal}.png' draggable='false'>`, 'ERA-DP');
//
				let html = `<H1 style='font-family:ERA;'>${_('Galactic Goal') + ' ' + _(this.bgagame.gamedatas.galacticGoal)}</H1>`;
				html += '<div style="text-align:justify;"><HR>' + bgagame.GALACTIC_GOALS[this.bgagame.gamedatas.galacticGoal] + '</div>';
//
				this.goal = new dijit.Tooltip({connectId: galacticGoalNode, showDelay: 500, hideDelay: 0, label: html, position: ['below']});
			}
		},
		dominationDialog: function (faction, id)
		{
			const node = $(`ERAdominationCard-${faction.color}-${id}`);
			if (!node) return;
//
			const domination = node.getAttribute('domination');
			const multiplier = this.bgagame.gamedatas.galacticGoal === 'Personal Growth' ? 2 : 1;
			const A = dojo.query(`#ERAdominationCard-${faction}-A`, 'ERAboard').length;
//
			this.dialog = new ebg.popindialog();
			this.dialog.create('ERAdominationDialog');
			this.dialog.setTitle(`<H1 style='font-family:ERA;'>${this.bgagame.DOMINATIONS[domination].title}</H1>`);
//
			html = `<div style='display:flex;flex-direction:row;'>`;
//
			html += node.outerHTML;
//
			html += `<div style='display:flex;flex-direction:column;justify-content:space-between;padding:25px;'>`;
//
			html += `<div style='display:flex;flex-direction:row;align-items:center;flex-wrap:wrap;'>`;
//
			html += `<div id='ERAdominationButtonAwithEffect' class='bgabutton bgabutton_red' style='visibility:${A ? 'hidden' : 'visible'};font-size:large;margin-right:10px;'>${_('A-Section')}</div>`;
			html += `<div id='ERAdominationButtonAwithoutEffect' class='bgabutton bgabutton_red' style='visibility:${A ? 'hidden' : 'visible'};font-size:x-small;text-align:center;;margin-right:10px;'>${_('A-Section<BR>(without effect)')}</div>`;
			html += `<div style='margin:20px;font-family:ERA;font-size:large;color:#${faction.color};'>`;
			html += `<div style='font-family:ERA;font-size:${this.bgagame.DOMINATIONS[domination].DP * multiplier * 2 + 10}pt'>${this.bgagame.DOMINATIONS[domination].DP * multiplier}  DP</div>`;
			html += `</div>`;
			html += `</div>`;
//
			html += `<div style='margin:10px;text-align:justify;'>`;
			html += `<HR>`;
			for (let text of this.bgagame.DOMINATIONS[domination].A) html += `<div>${text}</div>`;
			html += `<HR>`;
			html += `</div>`;
//
			html += `<div style='margin:10px;text-align:justify;opacity:${this.bgagame.gamedatas.factions[faction.color].scoring[domination].A ? 100 : 50}%;'>`;
			html += `<HR>`;
			html += `<div style='text-align:center;font-weight:bold;color:cyan;'>${_('Immediate Effect')}</div>`;
			html += `<HR>`;
			for (let text of this.bgagame.DOMINATIONS[domination].E) html += `<div>${text}</div>`;
			html += `<HR>`;
			html += `</div>`;
//
			html += `<div style='display:flex;flex-direction:row;align-items:center;flex-wrap:wrap;'>`;
			html += `<div id='ERAdominationButtonB' class='bgabutton bgabutton_red' style='font-size:large;'>${_('B-Section')}</div>`;
//
			html += `<div style='margin:20px;font-family:ERA;font-size:large;color:#${faction.color};'>`;
			for (let score of this.bgagame.gamedatas.factions[faction.color].scoring[domination].B)
				html += `<div style='font-family:ERA;font-size:${score * 2 + 10}pt'>${score} DP</div>`;
			html += `</div>`;
//
			html += `<div style='margin:10px;font-size:small;'>`;
			for (let text of this.bgagame.DOMINATIONS[domination].B) html += `<div>${text}</div>`;
			html += `</div>`;
//
			html += `</div>`;
//
			html += `</div>`;
//
			html += `</div>`;
//
			this.dialog.setContent(html);
			this.dialog.show();
			dojo.style('popin_ERAdominationDialog_underlay', 'visibility', 'hidden');
//
			dojo.connect($('popin_ERAdominationDialog'), 'click', () => this.dialog.destroy());
//
			if ('possibleactions' in this.bgagame.gamedatas.gamestate && this.bgagame.gamedatas.gamestate.possibleactions.includes('domination') && this.bgagame.gamedatas.factions[faction.color].scoring[domination].A)
			{
				dojo.connect($('ERAdominationButtonAwithEffect'), 'click', (event) => {
					if (this.bgagame.isCurrentPlayerActive())
					{
						dojo.stopEvent(event);
						this.bgagame.confirmationDialog(dojo.string.substitute(_('Play A-Section <B>WITH</B> One-time Effect'), {}), () =>
						{
							this.bgagame.action('domination', {color: faction.color, id: id, section: 'A', effect: true});
							this.dialog.destroy();
						});
					}
				});
				dojo.connect($('ERAdominationButtonAwithoutEffect'), 'click', (event) => {
					if (this.bgagame.isCurrentPlayerActive())
					{
						dojo.stopEvent(event);
						this.bgagame.confirmationDialog(dojo.string.substitute(_('Play A-Section <B>WITHOUT</B> One-time Effect'), {}), () =>
						{
							this.bgagame.action('domination', {color: faction.color, id: id, section: 'A', effect: false});
							this.dialog.destroy();
						});
					}
				});
			}
			else
			{
				dojo.addClass('ERAdominationButtonAwithEffect', 'disabled');
				dojo.addClass('ERAdominationButtonAwithoutEffect', 'disabled');
			}
//
			if ('possibleactions' in this.bgagame.gamedatas.gamestate && this.bgagame.gamedatas.gamestate.possibleactions.includes('domination'))
				dojo.connect($('ERAdominationButtonB'), 'click', (event) => {
					if (this.bgagame.isCurrentPlayerActive())
					{
						dojo.stopEvent(event);
						this.bgagame.confirmationDialog(dojo.string.substitute(_('Play B-Section and score <B>${DP} DP<B>'), {DP: Math.max(...this.bgagame.gamedatas.factions[faction.color].scoring[domination].B)}), () =>
						{
							this.bgagame.action('domination', {color: faction.color, id: id, section: 'B', effect: false});
							this.dialog.destroy();
						});
					}
				});
			else dojo.addClass('ERAdominationButtonB', 'disabled');
		},
		update: function (faction)
		{
			for (let entry in faction) if (entry in this.bgagame.gamedatas.factions[faction.color]) this.bgagame.gamedatas.factions[faction.color][entry] = faction[entry];
//
			if ('starPeople' in faction)
			{
				let node = dojo.query(`#ERAfaction-${faction.color} .ERAstarPeople,.ERApanel-${faction.color} .ERAstarPeople`).forEach((node) =>
				{
					dojo.setAttr(node, 'starPeople', faction.starPeople);
					if (faction.starPeople === 'none')
					{
						dojo.setAttr(node, 'STO', `${g_gamethemeurl}img/starPeoples/none.png`);
						dojo.setAttr(node, 'STS', `${g_gamethemeurl}img/starPeoples/none.png`);
						dojo.style(node, 'pointer-events', 'none');
					}
					else
					{
						dojo.setAttr(node, 'STO', `${g_gamethemeurl}img/starPeoples/${faction.starPeople}.STO.jpg`);
						dojo.setAttr(node, 'STS', `${g_gamethemeurl}img/starPeoples/${faction.starPeople}.STS.jpg`);
						dojo.style(node, 'pointer-events', '');
					}
					if ('alignment' in faction)
					{
						dojo.toggleClass(node, 'ERA-STS', faction.alignment === 'STS');
						if (dojo.hasClass(node, 'ERA-STS')) dojo.setAttr(node.querySelector('img'), 'src', dojo.getAttr(node, 'STS'));
						else dojo.setAttr(node.querySelector('img'), 'src', dojo.getAttr(node, 'STO'));
					}
				});
			}
//
			if ('domination' in faction)
			{
				dojo.empty(`ERAdominationCards-${faction.color}`);
				for (let index in faction.domination)
				{
					const domination = faction.domination[index];
					const node = dojo.place(this.bgagame.format_block('ERAdominationCard', {id: 'player-' + index, index: index, domination: domination, owner: faction.color}), `ERAdominationCards-${faction.color}`);
					dojo.setAttr(node.querySelector('img'), 'src', `${g_gamethemeurl}img/dominationCards/${domination}.jpg`);
					if (domination !== 'back')
					{
						dojo.toggleClass(node, 'ERAdominationA', this.bgagame.gamedatas.factions[faction.color].scoring[node.getAttribute('domination')].A);
						dojo.toggleClass(node, 'ERAdominationEffect', this.bgagame.gamedatas.factions[faction.color].scoring[node.getAttribute('domination')].effect);
						dojo.connect(node, 'click', (event) => {
							dojo.stopEvent(event);
							if (true) this.dominationDialog(faction, index, domination);
							else this.bgagame.focus(event.currentTarget);
						});
					}
					else
					{
						this.bgagame.ERAdominationCards.addTarget(node);
						dojo.connect(node, 'click', (event) => {
							dojo.stopEvent(event);
							if (this.bgagame.isCurrentPlayerActive() && dojo.hasClass(node, 'ERAselected') && this.bgagame.gamedatas.gamestate.possibleactions.includes('oneTimeEffect'))
								this.bgagame.action('oneTimeEffect', {color: this.bgagame.color, json: JSON.stringify({color: faction.color})});
						});
					}
					dojo.connect(node, 'transitionend', () => dojo.style(node, {'pointer-events': '', 'z-index': ''}));
				}
//
				dojo.empty(`ERAplayerDominationCards-${faction.color}`);
				for (let index in faction.domination)
				{
					const domination = faction.domination[index];
					const node = dojo.place(this.bgagame.format_block('ERAdominationCard', {id: index, index: index, domination: domination, owner: faction.color}), `ERAplayerDominationCards-${faction.color}`);
					dojo.setAttr(node.querySelector('img'), 'src', `${g_gamethemeurl}img/dominationCards/${domination}.jpg`);
					if (domination !== 'back')
					{
						dojo.toggleClass(node, 'ERAdominationA', this.bgagame.gamedatas.factions[faction.color].scoring[node.getAttribute('domination')].A);
						dojo.toggleClass(node, 'ERAdominationEffect', this.bgagame.gamedatas.factions[faction.color].scoring[node.getAttribute('domination')].effect);
						dojo.connect(node, 'click', (event) => {
							dojo.stopEvent(event);
							if (true) this.dominationDialog(faction, index, domination);
							else this.bgagame.focus(event.currentTarget);
						});
					}
					else
					{
						this.bgagame.ERAdominationCards.addTarget(node);
						dojo.connect(node, 'click', (event) => {
							dojo.stopEvent(event);
							if (this.bgagame.isCurrentPlayerActive() && dojo.hasClass(node, 'ERAselected') && this.bgagame.gamedatas.gamestate.possibleactions.includes('oneTimeEffect'))
								this.bgagame.action('oneTimeEffect', {color: this.bgagame.color, json: JSON.stringify({color: faction.color})});
						});
					}
					dojo.connect(node, 'transitionend', () => dojo.style(node, {'pointer-events': '', 'z-index': ''}));
//
// GOD MODE
//
					dojo.connect(node, 'oncontextmenu', (event) => {
//
						if (+this.bgagame.gamedatas.GODMODE === 1)
						{
							dojo.stopEvent(event);
							dojo.query('.godMode').remove();
//
							const menu = dojo.place(`<div class='godMode' id='godMode'><ul class='godMode-options' style='color:#${faction.color};'>Domination card</ul></div>`, 'ebd-body');
							menu.style.left = `${event.clientX - 10}px`;
							menu.style.top = `${event.clientY - 10}px`;
//
							dojo.place(`<li class='godMode-option'><HR></li>`, menu);
//
							for (let domination in this.bgagame.DOMINATIONS)
								dojo.place(`<li class='godMode-option' onclick="gameui.action('GODMODE', {god: JSON.stringify({action: 'domination', color: '${faction.color}', id: ${index}, domination: ${domination}})});">${this.bgagame.DOMINATIONS[domination].title}</li>`, menu);
//
							dojo.place(`<li class='godMode-option'><HR></li>`, menu);
							dojo.place(`<li class='godMode-option'>CANCEL</li>`, menu);
//
							dojo.connect(menu, 'onclick', () => dojo.destroy(menu));
						}
					});
//
// GOD MODE
//
				}
//
				if (faction.diplomatic)
				{
					const node = dojo.place(`<div id='ERAdominationEffectDiplomatic' class='ERAdominationEffectRemind'></div>`, `ERAplayerDominationCards-${faction.color}`, 'first');
					this.bgagame.addTooltip(node.id, this.bgagame.DOMINATIONS[5].title, this.bgagame.DOMINATIONS[5].E[0]);
				}
				if (faction.generalscientific)
				{
					const node = dojo.place(`<div id='ERAdominationGeneralScientific' class='ERAdominationEffectRemind'></div>`, `ERAplayerDominationCards-${faction.color}`, 'first');
					this.bgagame.addTooltip(node.id, this.bgagame.DOMINATIONS[9].title, this.bgagame.DOMINATIONS[9].E[0]);
				}
			}
//
			if ('DP' in faction)
			{
				const player_id = +this.bgagame.gamedatas.factions[faction.color].player_id;
				if (player_id === -2)
				{
					dojo.query('.ERAcounter-population', 'ERAoffboard').remove();
					for (let i = 0; i < faction.DP; i++)
					{
						let x = 8 + 13 * i;
						let y = 55;
						let node = dojo.place(this.bgagame.format_block('ERAcounter', {id: faction.color + '-population', color: faction.color, type: 'populationDisc', location: 'offbard'}), 'ERAoffboard');
						dojo.style(node, 'position', 'absolute');
						dojo.style(node, 'left', x + '%');
						dojo.style(node, 'top', y + '%');
						dojo.style(node, 'transform', 'scale(12.5%)');
						dojo.style(node, 'transform-origin', 'left top');
						dojo.style(node, 'pointer-events', 'none');
					}
				}
				if (player_id in this.bgagame.gamedatas.players)
				{
					if (player_id in this.bgagame.scoreCtrl) this.bgagame.scoreCtrl[player_id].setValue(faction.DP);
//
					let node = $(`ERAcounter-${faction.color}-DP`);
					if (!node) node = dojo.place(this.bgagame.format_block('ERAcounter', {id: faction.color + '-DP', color: faction.color, type: 'cylinder', location: faction.DP}), 'ERA-DP');
					dojo.style(node, 'position', 'absolute');
					dojo.setAttr(node, 'location', faction.DP);
//
					let x = 00, dx = 49;
					let y = 0, dy = 50;
					let DP = faction.DP % 50;
					if (DP < 0) DP += 50;
					let score = 0;
					while (score < DP)
					{
						if (score < 15) x += dx;
						else if (score < 25) y += dy;
						else if (score < 40) x -= dx;
						else if (score < 50) y -= dy;
						score += 1;
					}
					dojo.style(node, 'left', x + 'px');
					dojo.style(node, 'top', y + 'px');
//
					let nodes = dojo.query(`#ERA-DP .ERAcounter-cylinder`);
					let index = {};
					for (let node of nodes)
					{
						let DP = dojo.getAttr(node, 'location');
						if (!(DP in index)) index[DP] = 0;
						dojo.style(node, 'transform', `scale(.75) translate(+${index[DP] * node.clientWidth / 10}px, -${index[DP] * node.clientHeight / 5}px) `);
						dojo.style(node, 'z-index', index[DP] + 100);
						dojo.style(node, 'pointer-events', 'all');
						dojo.setAttr(node, 'title', dojo.string.substitute(_('${DP} destiny points'), {DP: DP}));
						index[DP]++;
					}
				}
			}
//
			if ('population' in faction)
			{
				const nodePopulationTrack = $(`ERApopulationTrack-${faction.color}`);
				if (nodePopulationTrack)
				{
					dojo.empty(nodePopulationTrack);
					for (let population = 1 + +faction.population; population < 40; population++)
					{
						let node = dojo.place(this.bgagame.format_block('ERAcounter', {id: faction.color + '-population', color: faction.color, type: 'populationDisc', location: 'populationTrack'}), nodePopulationTrack);
						dojo.setAttr(node, 'population', population);
						dojo.style(node, 'position', 'absolute');
						if (population === 0) [x, y] = [543, 122];
						else [x, y] = [47.7 * ((39 - population) % 13) - 78, 81 * (Math.floor((39 - population) / 13)) - 40];
						dojo.style(node, 'left', x + 'px');
						dojo.style(node, 'top', y + 'px');
						dojo.style(node, 'transform', 'scale(20%)');
					}
				}
				$(`ERApopulation-${faction.color}`).innerHTML = 39 - faction.population;
				if ($(`ERAbonus-${faction.color}`)) $(`ERAbonus-${faction.color}`).innerHTML = `(+${POPULATION[faction.population]})`;
			}
//
			const nodeTechnologiesTrack = $(`ERAtechTrack-${faction.color}`);
			if (nodeTechnologiesTrack)
			{
				for (let technology of ['Military', 'Spirituality', 'Propulsion', 'Robotics', 'Genetics'])
				{
					if (technology in faction)
					{
						let node = $(`ERAcounter-${faction.color}-${technology}`);
						if (!node) node = dojo.place(this.bgagame.format_block('ERAcounter', {id: faction.color + '-' + technology, color: faction.color, type: 'cube', location: technology}), nodeTechnologiesTrack);
						dojo.setAttr(node, 'title', _(technology) + ' ' + faction[technology]);
						dojo.setAttr(node, 'level', faction[technology]);
						dojo.style(node, 'position', 'absolute');
						dojo.style(node, 'top', (['Military', 'Spirituality', 'Propulsion', 'Robotics', 'Genetics'].indexOf(technology) * 92.5 + 85) + 'px');
						dojo.style(node, 'left', [0, 55, 119, 193, 281, 388, 519][faction[technology]] + 'px');
						dojo.style(node, 'transform', 'scale(75%)');
					}
				}
				if ('advancedFleetTactics' in faction)
				{
					const advancedFleetTactics = JSON.parse(faction.advancedFleetTactics);
					for (fleet in advancedFleetTactics)
					{
						const tactics = advancedFleetTactics[fleet];
						if (tactics)
						{
							const node = dojo.place(this.bgagame.format_block('ERAcounter', {id: '2x', color: faction.color, type: 'tactics', location: fleet}), nodeTechnologiesTrack);
							dojo.setAttr(node, 'tactics', tactics);
							dojo.style(node, 'position', 'absolute');
							dojo.style(node, 'left', 798 + 'px');
							dojo.style(node, 'top', {A: 18, B: 122, C: 225, D: 329, E: 433}[fleet] + 'px');
						}
					}
				}
			}
			dojo.query(`#ERAtechnologies-${faction.color} [technology]`).forEach((node) => {
				const technology = dojo.getAttr(node, 'technology');
				if (technology in faction)
				{
					dojo.setAttr(node, 'level', faction[technology]);
//
					let html = '';
					for (let i = 1; i <= 6; i++)
						html += `<span id='ERAtechnology-${faction.color}-${i}-${technology}' class='${i === +faction[technology] ? 'circleCurrent' : ''} ${i <= faction[technology] ? 'circleBlack' : 'circleWhite'}'>${i}</span>`;
					node.children[0].innerHTML = html;
//
					for (let i = 1; i <= 6; i++)
					{
						dojo.connect($(`ERAtechnology-${faction.color}-${i}-${technology}`), 'click', (event) => {
							if (+this.bgagame.gamedatas.GODMODE === 1) this.bgagame.action('GODMODE', {god: JSON.stringify({action: 'technology', color: faction.color, technology: technology, level: i})});
						});
					}
				}
			});
//
			if ('order' in faction) dojo.query(`.ERAorder[faction=${faction.color}]`).forEach((node) => dojo.setAttr(node, 'order', faction.order));
//
			if ('atWar' in faction)
			{
				const atWar = JSON.parse(faction.atWar);
				dojo.query(`.ERAcounter-peace[color='${faction.color}']`).forEach((node) => dojo.toggleClass(node, 'ERAhide', atWar.includes(dojo.getAttr(node, 'on'))));
				dojo.query(`.ERAcounter-war[color='${faction.color}']`).forEach((node) => dojo.toggleClass(node, 'ERAhide', !atWar.includes(dojo.getAttr(node, 'on'))));
			}
//
			if ('ships' in faction) dojo.query(`.ERAships[faction=${faction.color}]`).forEach((node) => node.innerHTML = `${faction.ships}/16`);
			if ('emergencyReserve' in faction) dojo.query(`.ERAemergencyReserve-${faction.color}`).toggleClass('ERAhide', faction.emergencyReserve !== '1');
			if (faction.player_id <= 0) dojo.query(`.ERAemergencyReserve-${faction.color}`).addClass('ERAhide');
		}
	}
	);
});
