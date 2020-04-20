<?php

namespace WEEEOpen\Tarallo\Database;

final class BulkDAO extends DAO {

	public function addBulk(String $identifier, String $type, String $json) {

		$statement = $this->getPDO()->prepare(
			'
INSERT INTO BulkTable (`BulkIdentifier`, `User`, `Type`, `JSON`) 
VALUES (:id, @taralloauditusername, :typ, :json)'
		);
		try {
			$statement->bindValue(':id', $identifier, \PDO::PARAM_STR);
			$statement->bindValue(':typ', $type, \PDO::PARAM_STR);
			$statement->bindValue(':json', $json, \PDO::PARAM_STR);
			$result = $statement->execute();
			assert($result === true, 'Add bulk');
		} catch(\PDOException $e) {
			if($e->getCode() === '23000' && $statement->errorInfo()[1] === 1062) {
				throw new DuplicateItemCodeException((string) $identifier, 'Bulk already exists: ' . (string) $identifier);
			}
			throw $e;
		} finally {
			$statement->closeCursor();
		}
	}
  
  //Get all imports from BulkTable
	public function getBulkImports(): array {
		$statement = $this->getPDO()->query('SELECT * FROM BulkTable');
		$imports = $statement->fetchAll();
		return $imports;
	}
  
  	//Delete a bulk import via identifier
	public function deleteBulkImport(string $identifier) {
		$statement = $this->getPDO()->prepare('DELETE FROM BulkTable WHERE Identifier = :id');
		try {
			$statement->bindValue(':id', $identifier, \PDO::PARAM_INT);
			$statement->execute();
		} catch(\PDOException $e) {
			$e->getMessage();
      }
			throw $e;
		} finally {
			$statement->closeCursor();

	//Formats JSON in a readable form ( in case of minfied ones )
	static function prettyPrint( $json )
	{
		$result = '';
		$level = 0;
		$in_quotes = false;
		$in_escape = false;
		$ends_line_level = NULL;
		$json_length = strlen( $json );

		for( $i = 0; $i < $json_length; $i++ ) {
			$char = $json[$i];
			$new_line_level = NULL;
			$post = "";
			if( $ends_line_level !== NULL ) {
				$new_line_level = $ends_line_level;
				$ends_line_level = NULL;
			}
			if ( $in_escape ) {
				$in_escape = false;
			} else if( $char === '"' ) {
				$in_quotes = !$in_quotes;
			} else if( ! $in_quotes ) {
				switch( $char ) {
					case '}': case ']':
					$level--;
					$ends_line_level = NULL;
					$new_line_level = $level;
					break;

					case '{': case '[':
					$level++;
					case ',':
						$ends_line_level = $level;
						break;

					case ':':
						$post = " ";
						break;

					case " ": case "\t": case "\n": case "\r":
					$char = "";
					$ends_line_level = $new_line_level;
					$new_line_level = NULL;
					break;
				}
			} else if ( $char === '\\' ) {
				$in_escape = true;
			}
			if( $new_line_level !== NULL ) {
				$result .= "\n".str_repeat( "\t", $new_line_level );
			}
			$result .= $char.$post;
		}

		return $result;
	}

	//Get an import's JSON from BulkTable and decodes it
	public function getDecodedJSON(int $Identifier): array {
		$statement = $this->getPDO()->prepare('SELECT JSON FROM BulkTable WHERE Identifier = :id');
		$importElement = null;
		try {
			$statement->bindValue(':id', $Identifier, \PDO::PARAM_INT);
			$statement->execute();
			$importElement = $statement->fetch();
			$importElement = json_decode($importElement["JSON"],JSON_OBJECT_AS_ARRAY);
		} catch(\PDOException $e) {
			$e->getMessage();
		} finally {
			$statement->closeCursor();
		}

		return $importElement;
	}

}

	public function checkDuplicatedIdentifier(String $identifier): bool {
		$statement = $this->getPDO()->prepare(
			'
		SELECT Identifier FROM BulkTable WHERE BulkIdentifier = :id
		'
		);
		try {
			$statement->bindValue(':id', $identifier, \PDO::PARAM_STR);
			$statement->execute();
			if($statement->rowCount() === 0) {
				return false;
			}
			return true;
		} finally {
			$statement->closeCursor();
		}
	}
}