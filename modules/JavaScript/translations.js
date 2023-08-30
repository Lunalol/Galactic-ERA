//
function GALATIC_STORIES(galacticStory)
{
	return{
		'Journeys': {
			1: [
				_('Every player with the STO alignment at the end of a round scores 1 DP'),
				_('All players score 1 DP for every player they are “in contact” with at the end of the round (including the puppet in a 2-player game)')
			],
			2: [
				_('Every player with the STS alignment at the end of a round scores 1 DP'),
				_('Every player “at war” with at least one other player at the end of the round scores 1 DP'),
				_('All players score 2 DP for every star outside of their home star sector that they take from another player'),
				_('All players score 2 DP for every battle they win outside of their home star sector. Battles where all opposing ships retreated before combat are not counted')
			],
			3: [
				_('Every player with the STO alignment at the end of a round scores 1 DP'),
				_('At the end of the round, each player who researched Spirituality in that round and has the highest level (ties allowed) in that field among all the players who also researched that, scores 7 minus their Spirituality level. The same applies for Propulsion. A Research action that did not result in an increased technology level does not count, neither for scoring nor for preventing scoring. (*)')
			]
		},
		'Migrations': [],
		'Rivalry': [],
		'Wars': []
	}[galacticStory];
}
