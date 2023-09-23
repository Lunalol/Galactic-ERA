define(["dojo", "dojo/_base/declare"], function (dojo, declare)
{
	return declare("Board", null,
	{
		constructor: function (bgagame)
		{
			console.log('board constructor');
//
// Reference to BGA game
//
			this.bgagame = bgagame;
//
// Getting playarea & board container and map dimensions
//
			this.boardWidth = boardWidth;
			this.boardHeight = boardHeight;
//
			this.hexagons = {};
			for (let sector of Object.values(bgagame.gamedatas.sectors))
			{
				const x0 = boardWidth / 2 + (1 + MARGIN / 100) * BOARDS[sector.position][0] * WIDTH;
				const y0 = boardHeight / 2 + (1 + MARGIN / 100) * BOARDS[sector.position][1] * HEIGHT;
//
				const node = dojo.place(this.bgagame.format_block('ERAsector', {id: sector.position, sector: sector.sector, x: x0 - 3.50 * WIDTH, y: y0 - 4.50 * HEIGHT, angle: sector.orientation * 60}), 'ERAboard');
//
				for (let hexagon in sector.shape)
				{
					const x = x0 + 0.5 * (sector.shape[hexagon].x * Math.cos(sector.orientation * Math.PI / 3.) - sector.shape[hexagon].y * Math.sin(sector.orientation * Math.PI / 3.));
					const y = y0 + 0.5 * (sector.shape[hexagon].x * Math.sin(sector.orientation * Math.PI / 3.) + sector.shape[hexagon].y * Math.cos(sector.orientation * Math.PI / 3.));
					this.hexagons[sector.position + ':' + hexagon] = {sector: sector.position, hexagon: hexagon, x: Math.round(x), y: Math.round(y), orientation: sector.orientation, shape: sector.shape[hexagon].shape};
				}
			}
//
			this.playarea = dojo.byId('ERAplayArea');
			this.board = dojo.byId('ERAboard');
//
			for (let faction of Object.values(this.bgagame.gamedatas.factions))
			{
				if (true)
				{
					const angle = 60 * (faction.player_id < 0 ? 1 : faction.homeStar) - 210;
//
					const node = dojo.place(this.bgagame.format_block('ERApanel', {color: faction.color}), 'ERAboard');
					if (faction.player_id <= 0)
					{
						dojo.style(`ERApopulationTrack-${faction.color}`, 'display', 'none');
						dojo.style(`ERAplayerAid-${faction.color}`, 'display', 'none');
					}
					dojo.style(node, 'transform-origin', 'center');
					dojo.style(node, 'transform', `scale(1) rotate(${angle}deg)`);
//
					const x = this.hexagons['0:+0+0+0'].x + (faction.player_id < 0 ? 5.75 + +faction.player_id : 2.25) * (this.hexagons[(faction.player_id < 0 ? 1 : faction.homeStar) + ':+0+0+0'].x - this.hexagons['0:+0+0+0'].x) - node.offsetWidth / 2;
					const y = this.hexagons['0:+0+0+0'].y + (faction.player_id < 0 ? 5.75 + +faction.player_id : 2.25) * (this.hexagons[(faction.player_id < 0 ? 1 : faction.homeStar) + ':+0+0+0'].y - this.hexagons['0:+0+0+0'].y) - node.offsetHeight / 2;
//
					dojo.style(node, 'position', 'absolute');
					dojo.style(node, 'left', x + 'px');
					dojo.style(node, 'top', y + 'px');
					dojo.connect($(`ERAplayerAid-${faction.color}`), 'click', (event) => {
						dojo.stopEvent(event);
						if (dojo.hasClass(event.currentTarget, 'ERAfocus'))
						{
							const playerAid = (1 + +dojo.getAttr(event.currentTarget, 'playerAid')) % 4;
							dojo.style(event.currentTarget, 'background-image', `url(${g_gamethemeurl}img/playerAids/${playerAid}.jpg)`);
							dojo.setAttr(event.currentTarget, 'playerAid', playerAid);
						}
						else this.bgagame.focus(event.currentTarget);
					});
					dojo.connect($(`ERAplayerAid-${faction.color}`), 'transitionend', (event) => dojo.style(event.currentTarget, {'pointer-events': '', 'z-index': ''}));
				}
			}
//
// Slider setting for zoom
//
			$('page-title').appendChild(dojo.byId('ERAcontrols'));
//
			this.zoomLevel = dojo.byId('ERAzoomLevel');
			const zoomLevelMin = Math.floor(Math.log10(Math.max(this.playarea.clientWidth / this.boardWidth, this.playarea.clientHeight / this.boardHeight)) * 100.);
			this.zoomLevel.min = zoomLevelMin;
			this.zoomLevel.max = 200 + zoomLevelMin;
//
			this.rotate = dojo.byId('ERArotate');
//
// Initial zoom to cover the whole map or stored in session
//
			const scale = parseFloat(localStorage.getItem(`${this.bgagame.game_id}.${this.bgagame.table_id}.zoomLevel`));
			const rotate = parseFloat(localStorage.getItem(`${this.bgagame.game_id}.${this.bgagame.table_id}.rotate`));
			const sX = parseFloat(localStorage.getItem(`${this.bgagame.game_id}.${this.bgagame.table_id}.sX`));
			const sY = parseFloat(localStorage.getItem(`${this.bgagame.game_id}.${this.bgagame.table_id}.sY`));
//
			if (isNaN(scale) || isNaN(rotate) || isNaN(sX) || isNaN(sY)) this.home(this.bgagame.player_id);
			else
			{
				this.setRotate(rotate);
				this.setZoom(Math.max(2. * this.playarea.clientWidth / this.boardWidth, 2. * this.playarea.clientHeight / this.boardHeight, scale), this.playarea.clientWidth / 2, this.playarea.clientHeight / 2);
				this.playarea.scrollLeft = sX;
				this.playarea.scrollTop = sY;
			}
//
// Flag to follow drag gestures
//
			this.dragging = false;
//
			dojo.connect($('leftright_page_wrapper'), 'oncontextmenu', (event) => dojo.stopEvent(event));
			dojo.connect(this.playarea, 'click', this, 'click')
//
// Event listeners for drag gestures
//
			dojo.connect(this.playarea, 'mousedown', this, 'begin_drag');
			dojo.connect(this.playarea, 'mousemove', this, 'drag');
			dojo.connect(this.playarea, 'mouseup', this, 'end_drag');
			dojo.connect(this.playarea, 'mouseleave', this, 'end_drag');
//
// Event listeners for scaling
//
			dojo.connect(this.playarea, 'scroll', this, 'scroll');
			dojo.connect(this.playarea, 'wheel', this, 'wheel');
			dojo.connect(this.zoomLevel, 'oninput', this, () => this.setZoom(Math.pow(10., event.target.value / 100), this.playarea.clientWidth / 2, this.playarea.clientHeight / 2));
			dojo.connect(dojo.byId('ERAzoomMinus'), 'onclick', () => this.setZoom(Math.pow(10., (parseInt(this.zoomLevel.value) - 5) / 100), this.playarea.clientWidth / 2, this.playarea.clientHeight / 2));
			dojo.connect(dojo.byId('ERAzoomPlus'), 'onclick', () => this.setZoom(Math.pow(10., (parseInt(this.zoomLevel.value) + 5) / 100), this.playarea.clientWidth / 2, this.playarea.clientHeight / 2));
			dojo.connect(this.rotate, 'oninput', this, () => this.setRotate(event.target.value));
			dojo.connect(dojo.byId('ERArotateAntiClockwise'), 'onclick', () => this.setRotate(Math.round(parseInt(this.rotate.value) / 30 - 1) * 30));
			dojo.connect(dojo.byId('ERArotateClockwise'), 'onclick', () => this.setRotate(Math.round(parseInt(this.rotate.value) / 30 + 1) * 30));
			dojo.connect(dojo.byId('ERAhome'), 'onclick', () => this.home(this.bgagame.player_id));
			dojo.connect(dojo.byId('ERAview'), 'onclick', () => {
				if ($('ERAchoice')) dojo.toggleClass('ERAchoice', 'ERAhide');
			});
//
			dojo.connect(this.playarea, 'gesturestart', this, () => this.zooming = this.board.scale);
			dojo.connect(this.playarea, 'gestureend', this, () => this.zooming = null);
			dojo.connect(this.playarea, 'gesturechange', this, (event) =>
			{
				event.preventDefault();
//
				if (this.zooming !== null)
				{
					const rect = this.playarea.getBoundingClientRect();
					this.setZoom(this.zooming * event.scale, event.clientX - rect.left, event.clientY - rect.top);
				}
			});
//
// Event listeners for hiding units/markers
//
			document.addEventListener('keydown', (event) => {
				if (event.key === 'Shift') dojo.addClass(this.board, 'ERAhideUnits');
				if (event.key === 'Control') dojo.addClass(this.board, 'ERAhideMarkers');
			});
			document.addEventListener('keyup', (event) => {
				if (event.key === 'Shift') dojo.removeClass(this.board, 'ERAhideUnits');
				if (event.key === 'Control') dojo.removeClass(this.board, 'ERAhideMarkers');
			});
			window.onblur = () => {
				dojo.removeClass(this.board, 'ERAhideUnits');
				dojo.removeClass(this.board, 'ERAhideMarkers');
			};
		},
		home: function (player_id)
		{
			if (player_id in this.bgagame.players && player_id >= 0)
			{
				const sector = this.bgagame.gamedatas.factions[this.bgagame.players[player_id]].homeStar;
				this.setZoom(10 * Math.min(this.playarea.clientWidth / this.boardWidth, this.playarea.clientHeight / this.boardHeight), this.playarea.clientWidth / 2, this.playarea.clientHeight / 2);
				this.setRotate(210 - 60 * sector);
				this.centerMap(sector + ':+0+0+0');
			}
			else
			{
				this.setZoom(10 * Math.min(this.playarea.clientWidth / this.boardWidth, this.playarea.clientHeight / this.boardHeight), this.playarea.clientWidth / 2, this.playarea.clientHeight / 2);
				this.centerMap('0:+0+0+0');
			}
		},
		setRotate: function (rotate)
		{
			if (rotate > 180) rotate -= 360;
			if (rotate < -180) rotate += 360;
//
// Calc scale and store in session
//
			localStorage.setItem(`${this.bgagame.game_id}.${this.bgagame.table_id}.rotate`, rotate);
//
// Update range value
//
			this.rotate.value = rotate;
			this.board.style.setProperty('--ROTATE', this.rotate.value + 'deg');
//
// Board rotating
//
		},
		setZoom: function (scale, x, y)
		{
//
// Calc scale and store in session
//
			scale = Math.max(this.playarea.clientWidth / this.boardWidth, this.playarea.clientHeight / this.boardHeight, scale);
			localStorage.setItem(`${this.bgagame.game_id}.${this.bgagame.table_id}.zoomLevel`, scale);
//
// Update range value
//
			this.zoomLevel.value = Math.round(Math.log10(scale) * 100.);
//
// Get scroll positions and scale before scaling
//
			let sX = this.playarea.scrollLeft;
			let sY = this.playarea.scrollTop;
//
// Board scaling
//
			const oldScale = this.board.scale;
			this.board.scale = scale;
			this.board.style.transform = `scale(${this.board.scale}) translate(${this.boardWidth / 2}px,${this.boardHeight / 2}px) rotate(var(--ROTATE)) translate(-${this.boardWidth / 2}px,${ -this.boardHeight / 2}px)`;
			this.board.style.width = `${this.boardWidth * Math.min(1.0, scale)}px`;
			this.board.style.height = `${this.boardHeight * Math.min(1.0, scale)}px`;
//
// Set scroll positions after scaling
//
			this.playarea.scrollTo(Math.round((x + sX) * (scale / oldScale) - x), Math.round((y + sY) * (scale / oldScale) - y));
		},
		wheel: function (event)
		{
// Alt + Wheel
			if (event.altKey)
			{
				dojo.stopEvent(event);
				this.setRotate(parseInt(this.rotate.value) + event.deltaY / 10);
			}
// Ctrl + Wheel
			if (event.ctrlKey)
			{
				dojo.stopEvent(event);
//
// Update scale only when zoom factor is updated
//
				const oldZoom = parseInt(this.zoomLevel.value);
				const newZoom = Math.min(Math.max(this.zoomLevel.min, oldZoom - 10 * Math.sign(event.deltaY)), this.zoomLevel.max);
				if (oldZoom !== newZoom)
				{
					const rect = this.playarea.getBoundingClientRect();
					this.setZoom(Math.pow(10., newZoom / 100.), event.clientX - rect.left, event.clientY - rect.top);
				}
			}
		},
		scroll: function ()
		{
			localStorage.setItem(`${this.bgagame.game_id}.${this.bgagame.table_id}.sX`, this.playarea.scrollLeft);
			localStorage.setItem(`${this.bgagame.game_id}.${this.bgagame.table_id}.sY`, this.playarea.scrollTop);
		},
		begin_drag: function (event)
		{
//
			this.startX = event.clientX;
			this.startY = event.clientY;
		},
		drag: function (event)
		{
			if (event.buttons !== 1) return;
			if (Math.max(Math.abs((event.clientX - this.startX), Math.abs(event.clientY - this.startY))) >= 2) this.dragging = true;
//
			if (this.dragging === true)
			{
				this.playarea.scrollLeft -= (event.clientX - this.startX);
				this.playarea.scrollTop -= (event.clientY - this.startY);
//
				this.startX = event.clientX;
				this.startY = event.clientY;
			}
		},
		end_drag: function ()
		{
			this.dragging = false;
//			if (this.dragging)
//			{
//				if (this.dragging === true)
//				{
//					window.setTimeout(() => {
//						this.dragging = false;
//					});
//				}
//				else
//				{
//					window.clearTimeout(this.dragging);
//					this.dragging = false;
//				}
//			}
		},
		centerMap: function (location)
		{
			if (!g_archive_mode)
			{
				let [x, y] = [this.boardWidth / 2, this.boardHeight / 2];
				if (location && location in this.hexagons) [x, y] = [this.hexagons[location].x, this.hexagons[location].y];
				const angle = parseFloat(this.rotate.value) * Math.PI / 180.;
				[x, y] = [
					this.boardWidth / 2 + Math.cos(angle) * (x - this.boardWidth / 2) - Math.sin(angle) * (y - this.boardHeight / 2),
					this.boardHeight / 2 + Math.sin(angle) * (x - this.boardWidth / 2) + Math.cos(angle) * (y - this.boardHeight / 2)
				];
				const zoom = parseFloat(this.board.scale);
				this.playarea.scrollTo({left: x * zoom - this.playarea.clientWidth / 2, top: y * zoom - this.playarea.clientHeight / 2, behavior: 'smooth'});
			}
		},
		click: function (event)
		{
			if (this.dragging === true) return;
//
			const rect = this.playarea.getBoundingClientRect();
			const scale = parseFloat(this.board.scale);
			const angle = parseFloat(this.rotate.value) * Math.PI / 180.;
			const zoom = window.getComputedStyle($('page-content')).zoom || 1;
			let x = (event.clientX / zoom + this.playarea.scrollLeft - rect.left) / scale;
			let y = (event.clientY / zoom + this.playarea.scrollTop - rect.top) / scale;
//
			[x, y] = [
				this.boardWidth / 2 + Math.cos(angle) * (x - this.boardWidth / 2) + Math.sin(angle) * (y - this.boardHeight / 2),
				this.boardHeight / 2 - Math.sin(angle) * (x - this.boardWidth / 2) + Math.cos(angle) * (y - this.boardHeight / 2)
			];
//
			let location = this.nearest(x, y);
			if (location !== undefined && this.bgagame.isCurrentPlayerActive())
			{
				if (this.bgagame.gamedatas.gamestate.name === 'combatChoice') return this.bgagame.combatChoice(location);
				if (this.bgagame.gamedatas.gamestate.name === 'retreat') return this.bgagame.retreat(location);
				if (this.bgagame.gamedatas.gamestate.name === 'retreatE') return this.bgagame.retreat(location);
				if (this.bgagame.gamedatas.gamestate.name === 'gainStar') return this.bgagame.gainStar(location);
				if (this.bgagame.gamedatas.gamestate.name === 'buriedShips') return this.bgagame.buildShips(location);
				if (this.bgagame.gamedatas.gamestate.name === 'buildShips') return this.bgagame.buildShips(location);
				if (this.bgagame.gamedatas.gamestate.name === 'growPopulation') return this.bgagame.growPopulation(location);
				if (this.bgagame.gamedatas.gamestate.name === 'bonusPopulation') return this.bgagame.bonusPopulation(location);
			}
			dojo.query('.ERAfocus').forEach((node) => {
				dojo.style(node, {'pointer-events': 'none', 'z-index': '1000', 'transform': ``});
				dojo.removeClass(node, 'ERAfocus');
			});
//
			if (['fleets', 'buriedShips', 'remoteViewing', 'movement'].includes(this.bgagame.gamedatas.gamestate.name)) this.bgagame.restoreServerGameState();
			if (!['buriedShips', 'buildShips'].includes(this.bgagame.gamedatas.gamestate.name)) this.bgagame.restoreServerGameState();
		},
		drawHexagon: function (hexagon, color)
		{
			let shape = Array.from(hexagon.shape);
			let angle = hexagon.orientation * Math.PI / 3.;
//
			let x0 = 0.5 * shape.shift();
			let y0 = 0.5 * shape.shift();
			let path = 'M' + Math.round(x0 * Math.cos(angle) - y0 * Math.sin(angle) + hexagon.x) + ' ' + Math.round(x0 * Math.sin(angle) + y0 * Math.cos(angle) + hexagon.y);
			while (shape.length > 0)
			{
				let x = 0.5 * shape.shift();
				let y = 0.5 * shape.shift();
				path += 'L' + Math.round(x * Math.cos(angle) - y * Math.sin(angle) + hexagon.x) + ' ' + Math.round(x * Math.sin(angle) + y * Math.cos(angle) + hexagon.y);
			}
			path += 'Z';
//
			const SVGpath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
			dojo.setAttr(SVGpath, 'id', hexagon.sector + ':' + hexagon.hexagon);
			dojo.setAttr(SVGpath, 'd', path);
			dojo.setAttr(SVGpath, 'fill', color);
			dojo.setAttr(SVGpath, 'stroke', 'none');
			dojo.style(SVGpath, 'filter', 'blur(10px');
//
			return SVGpath;
		},
		nearest(x, y)
		{
			let hexagon = undefined;
			let minimum = Infinity;
			for (let h in this.hexagons)
			{
				d = (x - this.hexagons[h].x) ** 2 + (y - this.hexagons[h].y) ** 2;
				if (d < minimum && d < SIZE * SIZE)
				{
					minimum = d;
					hexagon = h;
				}
			}
			return hexagon;
		},
		clearCanvas()
		{
			const ctx = this.canvas.getContext('2d');
			ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
		}
	}
	);
});
