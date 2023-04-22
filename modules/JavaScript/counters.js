define(["dojo", "dojo/_base/declare"], function (dojo, declare)
{
	return declare("Counters", null,
	{
		constructor: function (bgagame)
		{
			console.log('counters constructor');
//
// Reference to BGA game
//
			this.bgagame = bgagame;
			this.board = bgagame.board;
		},
		place: function (counter)
		{
			console.info('placeCounter', counter);
//
			const node = dojo.place(this.bgagame.format_block('ERAcounter', {id: counter.id, color: counter.color, type: counter.type, subtype: counter.subType, location: counter.location}), 'ERAboard');
//
			dojo.style(node, 'position', 'absolute');
			dojo.style(node, 'left', this.board.hexagons[counter.location].x - node.clientWidth / 2 + 'px');
			dojo.style(node, 'top', this.board.hexagons[counter.location].y - node.clientHeight / 2 + 'px');
//
			dojo.connect(node, 'click', this, 'click');
//
			if (/^\d:([+-]\d){3}$/.test(counter.location)) this.arrange(counter.location);
		},
		arrange: function (location)
		{
			let index = 0;
			const nodes = dojo.query(`#ERAboard .ERAcounter[location='${location}']`);
			for (const node of nodes)
			{
				dojo.style(node, 'transform', `translate(${4 * (index) * node.clientWidth / 10}px, -${2 * (index) * node.clientHeight / 10}px) rotate(calc(-1 * var(--ROTATE)))`);
				dojo.style(node, 'z-index', index + 100);
				index++;
			}
		}
		,
		click: function (event)
		{
			console.log('click : ', event.currentTarget.id);
//
//			dojo.stopEvent(event);
		}
	}
	);
});
