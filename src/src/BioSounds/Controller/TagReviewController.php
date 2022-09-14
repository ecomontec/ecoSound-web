<?php

namespace BioSounds\Controller;

use BioSounds\Entity\TagReview;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Exception\NotAuthenticatedException;
use BioSounds\Utils\Auth;

class TagReviewController extends BaseController
{
    /**
     * @param int $tagId
     * @return false|string
     * @throws \Exception
     */
	public function show(int $tagId)
    {
		if (!Auth::isUserLogged()) {
			throw new NotAuthenticatedException();
		}

		if (empty($tagId)) {
			throw new \Exception(ERROR_EMPTY_ID);
		}

		if(!Auth::isUserAdmin() &&
            (!isset($_SESSION['user_col_permission']) ||
                empty($_SESSION['user_col_permission']))
        ) {
			throw new ForbiddenException();
		}

		$tagReview = new TagReview();

        return $this->twig->render('tag/tagReview.html.twig', [
            'disableReviewForm' => !Auth::isUserAdmin() && $tagReview->hasUserReviewed(Auth::getUserLoggedID(), $tagId),
            'reviews' => $tagReview->getListByTag($tagId),
            'tagId' => $tagId,
        ]);
	}

    /**
     * @return array|bool|int|null
     * @throws \Exception
     */
	public function save()
    {
        if (!Auth::isUserLogged()) {
            throw new NotAuthenticatedException();
        }

        if(!Auth::isUserAdmin() &&
            (!isset($_SESSION['user_col_permission']) ||
                empty($_SESSION['user_col_permission']))
        ) {
            throw new ForbiddenException();
        }

        $data[TagReview::USER] = Auth::getUserLoggedID();

        foreach ($_POST as $key => $value) {
            $data[$key] = htmlentities(strip_tags($value), ENT_QUOTES);
        }

        if (empty($data[TagReview::SPECIES])) {
            unset($data[TagReview::SPECIES]);
        }

        (new TagReview())->insert($data);

        return json_encode([
            'errorCode' => 0,
            'message' => 'Tag review saved successfully.',
        ]);
	}
}
