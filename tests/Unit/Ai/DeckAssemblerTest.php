<?php

declare(strict_types=1);

use App\Ai\DeckAssembler;
use App\Domain\Presentation\ValueObjects\FlowNodeType;
use App\Domain\Presentation\ValueObjects\SlideLayout;

function assemble(array $deck): array
{
    return (new DeckAssembler)->assemble($deck);
}

it('builds slides, assigns block ids, and places blocks in their slot', function () {
    $result = assemble([
        'title' => 'My Talk',
        'slides' => [
            [
                'layout' => 'left-right',
                'title' => 'Intro',
                'blocks' => [
                    ['slot' => 'left', 'type' => 'text', 'content' => 'Hello'],
                    ['slot' => 'right', 'type' => 'code', 'content' => 'echo 1;', 'lang' => 'php'],
                ],
            ],
        ],
    ]);

    $slide = $result['content']->slides[0];

    expect($result['content']->slides)->toHaveCount(1)
        ->and($slide->id)->toBe('slide-1')
        ->and($slide->layout)->toBe(SlideLayout::LeftRight)
        ->and($slide->title)->toBe('Intro')
        ->and($slide->slots['left'][0]->id)->toBe('b-1-1')
        ->and($slide->slots['left'][0]->content)->toBe('Hello')
        ->and($slide->slots['right'][0]->lang)->toBe('php');
});

it('clamps blocks in an unknown slot to the first legal slot for the layout', function () {
    $result = assemble([
        'title' => 'T',
        'slides' => [
            [
                'layout' => 'center',
                'blocks' => [
                    ['slot' => 'nonsense', 'type' => 'text', 'content' => 'X'],
                ],
            ],
        ],
    ]);

    $slots = $result['content']->slides[0]->slots;

    expect($slots)->toHaveKey('main')
        ->and($slots['main'][0]->content)->toBe('X');
});

it('falls back to an unknown layout and block type without throwing', function () {
    $result = assemble([
        'title' => 'T',
        'slides' => [
            [
                'layout' => 'made-up',
                'blocks' => [
                    ['slot' => 'main', 'type' => 'wat', 'content' => 'X'],
                ],
            ],
        ],
    ]);

    $slide = $result['content']->slides[0];

    expect($slide->layout)->toBe(SlideLayout::Center)
        ->and($slide->slots['main'][0]->type)->toBe('text');
});

it('chains slide nodes sequentially with one nav edge between them', function () {
    $result = assemble([
        'title' => 'T',
        'slides' => [
            ['layout' => 'center', 'blocks' => [['slot' => 'main', 'type' => 'text', 'content' => 'A']]],
            ['layout' => 'center', 'blocks' => [['slot' => 'main', 'type' => 'text', 'content' => 'B']]],
            ['layout' => 'center', 'blocks' => [['slot' => 'main', 'type' => 'text', 'content' => 'C']]],
        ],
    ]);

    $flow = $result['flow'];

    $slideNodes = array_filter($flow->nodes, fn ($n) => $n->type === FlowNodeType::Slide);
    $navEdges = array_filter($flow->edges, fn ($e) => str_starts_with($e->id, 'e-nav-'));

    expect($slideNodes)->toHaveCount(3)
        ->and($navEdges)->toHaveCount(2);
});

it('turns reveal steps into anchored, chained transition nodes and pins blocks to them', function () {
    $result = assemble([
        'title' => 'T',
        'slides' => [
            [
                'layout' => 'center',
                'blocks' => [
                    ['slot' => 'main', 'type' => 'text', 'content' => 'Base'],
                    ['slot' => 'main', 'type' => 'text', 'content' => 'First', 'revealStep' => 1],
                    ['slot' => 'main', 'type' => 'text', 'content' => 'Second', 'revealStep' => 2],
                ],
            ],
        ],
    ]);

    $blocks = $result['content']->slides[0]->slots['main'];
    $transitions = array_values(array_filter(
        $result['flow']->nodes,
        fn ($n) => $n->type === FlowNodeType::Transition,
    ));

    expect($blocks[0]->transition)->toBeNull()
        ->and($blocks[1]->transition?->nodeId)->toBe('n-slide-1-step-1')
        ->and($blocks[2]->transition?->nodeId)->toBe('n-slide-1-step-2')
        ->and($transitions)->toHaveCount(2)
        ->and($transitions[0]->data['slideId'])->toBe('slide-1');

    // The two steps are chained in order, and the whole graph validates.
    $chainEdges = array_filter($result['flow']->edges, fn ($e) => str_starts_with($e->id, 'e-chain-'));
    expect($chainEdges)->toHaveCount(1);
});

it('produces a graph whose slide nodes all reference existing slides', function () {
    $result = assemble([
        'title' => 'T',
        'slides' => [
            ['layout' => 'center', 'blocks' => [['slot' => 'main', 'type' => 'text', 'content' => 'A']]],
            ['layout' => 'top-main', 'blocks' => [['slot' => 'top', 'type' => 'text', 'content' => 'B']]],
        ],
    ]);

    $slideIds = array_map(fn ($s) => $s->id, $result['content']->slides);

    expect($result['flow']->referencedSlideIds())->toEqualCanonicalizing($slideIds);
});
