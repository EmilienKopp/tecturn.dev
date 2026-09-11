<?php

declare(strict_types=1);

namespace App\Ai;

use App\Domain\Presentation\ValueObjects\Block;
use App\Domain\Presentation\ValueObjects\BlockStyle;
use App\Domain\Presentation\ValueObjects\FlowEdge;
use App\Domain\Presentation\ValueObjects\FlowGraph;
use App\Domain\Presentation\ValueObjects\FlowNode;
use App\Domain\Presentation\ValueObjects\FlowNodeType;
use App\Domain\Presentation\ValueObjects\NodePosition;
use App\Domain\Presentation\ValueObjects\PresentationContent;
use App\Domain\Presentation\ValueObjects\Slide;
use App\Domain\Presentation\ValueObjects\SlideLayout;
use App\Domain\Presentation\ValueObjects\Transition;

/**
 * Translates the DeckArchitect agent's structured output into the domain value
 * objects. The agent describes slides loosely (layout, slots, blocks, per-block
 * reveal steps); this class enforces every invariant the value objects require:
 * stable ids, blocks clamped to layout-legal slots, and a valid flow graph
 * where slides chain in order and reveal steps become anchored transition nodes.
 */
class DeckAssembler
{
    private const float SLIDE_NODE_GAP = 340.0;

    private const float STEP_NODE_GAP = 90.0;

    /**
     * @param  array<string, mixed>  $deck
     * @return array{content: PresentationContent, flow: FlowGraph}
     */
    public function assemble(array $deck): array
    {
        $rawSlides = is_array($deck['slides'] ?? null) ? array_values($deck['slides']) : [];

        $slides = [];
        $nodes = [];
        $edges = [];
        $previousSlideNodeId = null;

        foreach ($rawSlides as $index => $rawSlide) {
            if (! is_array($rawSlide)) {
                continue;
            }

            $slideNumber = $index + 1;
            $slideId = "slide-{$slideNumber}";
            $layout = SlideLayout::tryFrom((string) ($rawSlide['layout'] ?? '')) ?? SlideLayout::Center;

            $rawBlocks = is_array($rawSlide['blocks'] ?? null) ? array_values($rawSlide['blocks']) : [];

            $stepNodeIds = $this->stepNodeIds($slideId, $rawBlocks);

            $slides[] = new Slide(
                id: $slideId,
                layout: $layout,
                background: null,
                slots: $this->buildSlots($slideNumber, $layout, $rawBlocks, $stepNodeIds),
                title: $this->stringOrNull($rawSlide['title'] ?? null),
            );

            $slideNodeId = "n-{$slideId}";

            $nodes[] = new FlowNode(
                id: $slideNodeId,
                type: FlowNodeType::Slide,
                position: new NodePosition(x: $index * self::SLIDE_NODE_GAP, y: 0.0),
                data: ['slideId' => $slideId],
            );

            if ($previousSlideNodeId !== null) {
                $edges[] = new FlowEdge(
                    id: "e-nav-{$index}",
                    source: $previousSlideNodeId,
                    target: $slideNodeId,
                );
            }

            $previousSlideNodeId = $slideNodeId;

            $this->appendStepNodesAndChain($slideId, $index, $stepNodeIds, $nodes, $edges);
        }

        return [
            'content' => new PresentationContent(
                version: PresentationContent::VERSION,
                slides: $slides,
            ),
            'flow' => new FlowGraph(
                version: FlowGraph::VERSION,
                nodes: $nodes,
                edges: $edges,
            ),
        ];
    }

    /**
     * Map the distinct reveal steps used on a slide (>= 1, ascending) to stable
     * transition node ids, so blocks and flow nodes agree on the same ids.
     *
     * @param  list<mixed>  $rawBlocks
     * @return array<int, string> reveal step => transition node id
     */
    private function stepNodeIds(string $slideId, array $rawBlocks): array
    {
        $steps = [];

        foreach ($rawBlocks as $rawBlock) {
            $step = is_array($rawBlock) ? (int) ($rawBlock['revealStep'] ?? 0) : 0;

            if ($step >= 1) {
                $steps[$step] = true;
            }
        }

        ksort($steps);

        $nodeIds = [];

        foreach (array_keys($steps) as $step) {
            $nodeIds[$step] = "n-{$slideId}-step-{$step}";
        }

        return $nodeIds;
    }

    /**
     * @param  list<mixed>  $rawBlocks
     * @param  array<int, string>  $stepNodeIds
     * @return array<string, list<Block>>
     */
    private function buildSlots(int $slideNumber, SlideLayout $layout, array $rawBlocks, array $stepNodeIds): array
    {
        $allowedSlots = $layout->slots();
        $fallbackSlot = $allowedSlots[0];
        $freeform = $layout->usesFreeformSlots();

        $slots = [];

        foreach ($rawBlocks as $position => $rawBlock) {
            if (! is_array($rawBlock)) {
                continue;
            }

            $type = (string) ($rawBlock['type'] ?? 'text');

            if (! in_array($type, Block::TYPES, true)) {
                $type = 'text';
            }

            $requestedSlot = (string) ($rawBlock['slot'] ?? '');
            $slot = ($freeform || ! in_array($requestedSlot, $allowedSlots, true))
                ? $fallbackSlot
                : $requestedSlot;

            $step = (int) ($rawBlock['revealStep'] ?? 0);
            $transition = ($step >= 1 && isset($stepNodeIds[$step]))
                ? new Transition(nodeId: $stepNodeIds[$step])
                : null;

            $slots[$slot][] = new Block(
                id: "b-{$slideNumber}-".($position + 1),
                type: $type,
                content: (string) ($rawBlock['content'] ?? ''),
                style: new BlockStyle,
                transition: $transition,
                lang: $type === 'code' ? $this->stringOrNull($rawBlock['lang'] ?? null) : null,
                src: in_array($type, ['image', 'qr'], true) ? $this->stringOrNull($rawBlock['src'] ?? null) : null,
                alt: $type === 'image' ? $this->stringOrNull($rawBlock['alt'] ?? null) : null,
            );
        }

        return $slots;
    }

    /**
     * Add a transition node per reveal step and chain them in step order so the
     * runtime plays the steps in sequence. Steps anchor to their slide through
     * the `slideId` in node data, never through an edge to the slide node.
     *
     * @param  array<int, string>  $stepNodeIds
     * @param  list<FlowNode>  $nodes
     * @param  list<FlowEdge>  $edges
     */
    private function appendStepNodesAndChain(string $slideId, int $slideIndex, array $stepNodeIds, array &$nodes, array &$edges): void
    {
        $previousStepNodeId = null;
        $offset = 0;

        foreach ($stepNodeIds as $step => $nodeId) {
            $nodes[] = new FlowNode(
                id: $nodeId,
                type: FlowNodeType::Transition,
                position: new NodePosition(
                    x: $slideIndex * self::SLIDE_NODE_GAP,
                    y: 120.0 + $offset * self::STEP_NODE_GAP,
                ),
                data: ['slideId' => $slideId, 'label' => "Step {$step}"],
            );

            if ($previousStepNodeId !== null) {
                $edges[] = new FlowEdge(
                    id: "e-chain-{$slideId}-{$step}",
                    source: $previousStepNodeId,
                    target: $nodeId,
                );
            }

            $previousStepNodeId = $nodeId;
            $offset++;
        }
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
