
# Nine men's morris
Nine men's morris is a strategy board game for two players dating at least to the Roman Empire.
You can access its api from here: https://users.it.teithe.gr/~it174861/ADISE21_Chris_174861/www/nine_men_morris.php

## Rules
The game proceeds in three phases:

1. Placing men on vacant points
2. Moving men to adjacent points
3. Moving men to any vacant point when the player has been reduced to three men

Phase 1: Placing pieces
Nine men's morris starts on an empty board.
The game begins with an empty board. The players determine who plays first, then take turns placing their men one per play on empty points. If a player is able to place three of their pieces on contiguous points in a straight line, vertically or horizontally, they have formed a mill and may remove one of their opponent's pieces from the board and the game, with the caveat that a piece in an opponent's mill can only be removed if no other pieces are available. After all men have been placed, phase two begins.

Phase 2: Moving pieces
Players continue to alternate moves, this time moving a man to an adjacent point. A piece may not "jump" another piece. Players continue to try to form mills and remove their opponent's pieces as in phase one. A player can "break" a mill by moving one of his pieces out of an existing mill, then moving it back to form the same mill a second time (or any number of times), each time removing one of his opponent's men. The act of removing an opponent's man is sometimes called "pounding" the opponent. When one player has been reduced to three men, phase three begins.

Phase 3: "Flying"
When a player is reduced to three pieces, there is no longer a limitation on that player of moving to only adjacent points: The player's men may "fly" from any point to any vacant point.

## API
Print board:
```bash
curl -X GET 'https://users.it.teithe.gr/~it174861/ADISE21_Chris_174861/www/nine_men_morris.php/board'
```
Reset board:
```bash
curl -X POST 'https://users.it.teithe.gr/~it174861/ADISE21_Chris_174861/www/nine_men_morris.php/board'
```

Show info about a piece:
```bash
curl -X GET \
	 -H 'Content-Type: application/json' \
	 -d '{"x":"x", "y":"y"}' \
	 'https://users.it.teithe.gr/~it174861/ADISE21_Chris_174861/www/nine_men_morris.php/board/piece'
```
Place or move piece (this will depend on how many pieces you have remaining):  
1. When placing a piece:
```bash
curl -X PUT \
	 -H 'Content-Type: application/json' \
	 -d '{"token":"your_token", "x":"x", "y":"y"}' \
	 'https://users.it.teithe.gr/~it174861/ADISE21_Chris_174861/www/nine_men_morris.php/board/piece'
```
2. When moving a piece:
```bash
curl -X PUT \
	 -H 'Content-Type: application/json' \
	 -d '{"token":"your_token", "x1":"x1", "y1":"y1", "x2":"x2", "y2":"y2"}' \
	 'https://users.it.teithe.gr/~it174861/ADISE21_Chris_174861/www/nine_men_morris.php/board/piece'
```

Show status:
```bash
curl -X GET 'https://users.it.teithe.gr/~it174861/ADISE21_Chris_174861/www/nine_men_morris.php/status'
```

Choose color and username:
```bash
curl -X PUT \
	 -H 'Content-Type: application/json' \
	 -d '{"username":"name"}' \
	 'https://users.it.teithe.gr/~it174861/ADISE21_Chris_174861/www/nine_men_morris.php/players/color'
```

Show all user info:
```bash
curl -X GET 'https://users.it.teithe.gr/~it174861/ADISE21_Chris_174861/www/nine_men_morris.php/players'
```

Show selected user info:
```bash
curl -X GET 'https://users.it.teithe.gr/~it174861/ADISE21_Chris_174861/www/nine_men_morris.php/players/color'
```

Reset game:
```bash
curl -X POST 'https://users.it.teithe.gr/~it174861/ADISE21_Chris_174861/www/nine_men_morris.php/players/reset'
```
