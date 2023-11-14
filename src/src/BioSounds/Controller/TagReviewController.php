<?php

namespace BioSounds\Controller;

use BioSounds\Entity\TagReview;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Exception\NotAuthenticatedException;
use BioSounds\Provider\TagProvider;
use BioSounds\Utils\Auth;

class TagReviewController extends BaseController
{
    /**
     * @param int $tagId
     * @return false|string
     * @throws \Exception
     */
    public function show(int $tagId, bool $isReviewGranted)
    {
        if (empty($tagId)) {
            throw new \Exception(ERROR_EMPTY_ID);
        }

        $tagReview = new TagReview();

        return $this->twig->render('tag/tagReview.html.twig', [
            'disableReviewForm' => !Auth::isUserAdmin() && $tagReview->hasUserReviewed(Auth::getUserLoggedID(), $tagId),
            'reviews' => $tagReview->getListByTag($tagId),
            'tagId' => $tagId,
            'isReviewGranted' => $isReviewGranted
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

        $data[TagReview::USER] = Auth::getUserLoggedID();

        foreach ($_POST as $key => $value) {
            $data[$key] = htmlentities(strip_tags($value), ENT_QUOTES);
        }

        if (empty($data[TagReview::SPECIES])) {
            unset($data[TagReview::SPECIES]);
        }
        if (isset($data['user_id_hidden'])) {
            unset($data['user_id']);
            (new TagReview())->update($data);
            return json_encode([
                'errorCode' => 0,
                'message' => 'Tag review updated successfully.'
            ]);
        } else {
            (new TagReview())->insert($data);
            return json_encode([
                'errorCode' => 0,
                'message' => 'Tag review saved successfully.',
            ]);
        }
    }

    public function delete(string $id)
    {
        (new TagReview())->delete($id);

        return json_encode([
            'errorCode' => 0,
            'message' => 'Review deleted successfully.',
        ]);
    }

    public function export(int $collection_id)
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }

        $colArr = [];
        $file_name = "reviews.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);
        $columns = (new TagReview())->getColumns();
        foreach ($columns as $column) {
            $colArr[] = $column['COLUMN_NAME'];
        }

        array_splice($colArr, 2, 0, 'user');
        array_splice($colArr, 4, 0, 'tag_review_status');
        array_splice($colArr, 6, 0, 'species');

        $Als[] = $colArr;
        $List = (new TagReview())->getReview($collection_id);
        foreach ($List as $Item) {
            $valueToMove = $Item['user'];
            unset($Item['user']);
            array_splice($Item, 2, 0, $valueToMove);
            $valueToMove = $Item['tag_review_status'];
            unset($Item['tag_review_status']);
            array_splice($Item, 4, 0, $valueToMove);
            $valueToMove = $Item['species'];
            unset($Item['species']);
            array_splice($Item, 6, 0, $valueToMove);

            $Als[] = $Item;
        }
        foreach ($Als as $line) {
            fputcsv($fp, $line);
        }
        fclose($fp);
        exit();
    }
}
