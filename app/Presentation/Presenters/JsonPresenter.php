<?php

declare(strict_types=1);

namespace App\Presentation\Presenters;

use App\Domain\Presentation\ValueObjects\FlowGraph;
use App\Domain\Presentation\ValueObjects\PresentationContent;
use App\Presentation\Contracts\Presenter;
use App\Presentation\PresenterOutput;

class JsonPresenter implements Presenter
{
    public function present(
        PresentationContent $content,
        string $name,
        ?FlowGraph $flowGraph = null
    ): PresenterOutput {

        return new PresenterOutput(
            json_encode(
                [
                    'content' => $content,
                    'name' => $name,
                    'flowGraph' => $flowGraph,
                ],
                JSON_THROW_ON_ERROR
            ),
            'application/json',
            filename: $name,
        );
    }
}
