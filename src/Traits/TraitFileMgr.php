<?php
namespace App\Traits;

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
}