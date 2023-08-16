<?php
namespace App\Traits;

use App\Entity\Book;

trait TraitFileMgr
{
    /**
     * To be used in BookController and AuthorController
     */
    public function RemoveOdtAndDirectory($booksInfo, $session)
    {
        foreach( $booksInfo as $book ){

            //
			// unix cmd
			// remove odt file
			passthru('rm -v -f books/'. $book['filename'] . ' > /dev/null 2>&1', $errCode );
			if ($errCode){
				$this->logger->info('Erreur de suppression du fichier : books/' . $book['filename'] . ' [$errCode:' . $errCode . ']');
				$this->addFlash('danger', 'Erreur de suppression du fichier : books/' . $book['filename'] . ' [$errCode:' . $errCode . ']');
			}
			else {
				$this->logger->info('Suppression du fichier : books/' . $book['filename'] . ' (with title : ' . $book['title'] . '),');
				$this->logger->info('qui avait été analysé en : ' . $book['parsingtime'] . 'secondes !!!');
                // $this->addFlash('success', 'Suppression du fichier : ' . $book['filename'] . ' OK.' );

			}
	
			// remove .whatever to get directory name from odt file name << maybe buggy !-(
			$dirName = substr($book['filename'], 0, strpos($book['filename'], '.'));
			
			// then delete associated directory recursive
			passthru('rm -v -r books/' . $dirName . ' > /dev/null 2>&1', $errCode );
			if ($errCode){
				$this->logger->info('Erreur de suppression du répertoire : ' . $dirName );
				$this->addFlash('error', 'Erreur de suppression du Répertoire : ' . $dirName . '[$errCode:' . $errCode . ']' );
			}
			else {
				$this->logger->info('Suppression du répertoire : books/' . $dirName . ' ET de son arborescence !' );
                $this->addFlash('success', 'Suppression des fichiers associés au document : ' . $book['filename'] . ' effectuée.' );
			}
	
			//
			// clear the array 'currentBookSelectionIds' in the session if any, and if deleted book is part of it
			// $session = $request->getSession();
			$currentBookSelectionIds = $session->get('currentBookSelectionIds');
			if ($currentBookSelectionIds){
				$this->logger->info('Ya une liste sélectionnée !!!' );
				if (in_array( $book['id'], $currentBookSelectionIds)){
	
					$i = array_search($book['id'], $currentBookSelectionIds);
					$this->logger->info("Et l'ouvrage est dedans ...");
					$splice = array_splice($currentBookSelectionIds, $i, 1 );
	
					$session->set('currentBookSelectionIds', $currentBookSelectionIds);
				}
			}
        }
    }

	/**
	 * Return dirName where is located the xml file to parse or false
	 */
	public function isOdtDocValid($odtFilePath): ? string
	{
		//
		// get the xml files out of odt file

		if (!file_exists($odtFilePath)){
			$this->logger->info( '$odtFilePath : ' . $odtFilePath . ' does not exist !!!');
			// internal error !!
			return null;
		}

		$dirName = \pathinfo($odtFilePath, PATHINFO_DIRNAME) . '/' . \pathinfo($odtFilePath, PATHINFO_FILENAME);

		//
		// unix cmd
		passthru('mkdir -v ' . $dirName . ' > /dev/null 2>&1', $errCode );
		if ($errCode){
			$this->logger->debug('Erreur de création du répertoire : ' . $dirName . ', errCode : ' . $errCode );
			$this->addFlash('error', 'Erreur de création du répertoire : ' . $dirName . ', errCode : ' . $errCode );
			
			// on oublie l'erreur produite si le répertoire existe déjà !
			// ça permet de tenter un dézip du document odt ...
			// return null;
		}
		//
		//
		passthru('unzip '. $odtFilePath . ' -d ' . $dirName . ' > /dev/null 2>&1', $errCode);
		if ($errCode){
			$this->logger->debug('Erreur de décompression : ' . $odtFilePath . ', errCode : ' . $errCode );
			$this->addFlash('danger', 'Erreur de décompression du fichier : ' . $odtFilePath);
			return null;
		}
		//
		//
		//
		//
		$xmlFileName	= $dirName . '/content.xml';
		$styleFileName 	= $dirName . '/styles.xml';
		$docFileName	= $dirName . '/document.xml';

		if (!file_exists($xmlFileName)){
			// internal error !!
			$this->logger->info( '$xmlFileName : ' . $xmlFileName . ' does not exist !!!');
			// $this->addFlash('danger', 'Fichier : ' . $odtFilePath . ' inexistant');

			return null;
		}
		if (!file_exists($styleFileName)){
			// internal error !!
			//$this->logger->info( '$styleFileName : ' . $styleFileName . ' does not exist !!!');
			return null;
		}

		$b1 = $this->projectDir . '/public/balise-bibnphi-1.xml';
		$b2 = $this->projectDir . '/public/balise-bibnphi-2.xml';

		// remove xml prolog in files $styleFileName and $xmlFileName
		//
		// supprime la 1ère ligne du fichier fileName avec backup dans fileName.bak
		// sed -i".bak" "1d" fileName

		passthru('sed -i "1d" ' . $styleFileName, $errCode); // sans back up ..
		if ($errCode){
			$this->logger->debug('Err: ' . $errCode . ', sed "1d" sur ' . $styleFileName);
			return null;
		}
		passthru('sed -i "1d" ' . $xmlFileName, $errCode); // sans back up ..
		if ($errCode){
			$this->logger->debug('Err: ' . $errCode . ', sed "1d" sur ' . $xmlFileName);
			return null;
		}

		// then build bibnphi document.xml ..

		passthru('cat '. $b1 . ' ' . $styleFileName . ' ' . $xmlFileName . ' ' . $b2 . ' > ' . $docFileName, $errCode);
		if ($errCode){
			$this->logger->debug('Err: ' . $errCode . ', lors de la concatenation de : ' . $xmlFileName . ' avec ' . $styleFileName);
			return null;
		}

		// success ..
		// un fichier document.xml est présent dans le répertoire de travail
		return $dirName;
	}

}