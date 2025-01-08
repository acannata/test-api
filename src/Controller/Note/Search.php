<?php

declare(strict_types=1);

namespace App\Controller\Note;

use Slim\Http\Request;
use Slim\Http\Response;

final class Search extends Base
{
    /**
     * @param array<string> $args
     */
    public function __invoke(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $notes = $this->getServiceFindNote()->search($args['query']);

        return $this->jsonResponse($response, 'success', $notes, 200);
    }
}
