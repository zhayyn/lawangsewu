<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    size: {
        type: Number,
        default: 30,
    },
});

const renderPaddingFactor = 1.7;

const canvasElement = ref(null);
const animationFrameHandle = ref(null);
const interactionSpeedFactor = ref(0);
const targetInteractionSpeedFactor = ref(0);
const pointerTiltX = ref(0);
const pointerTiltY = ref(0);
const targetPointerTiltX = ref(0);
const targetPointerTiltY = ref(0);
const floatingPhase = ref(0);
const orbitRotationPhase = ref(0);

const basePalette = Object.freeze({
    accentBlue: '#58a6ff',
    outline: '#c9d1d9',
    cubeFill: 'rgba(88, 166, 255, 0.08)',
});

const viewportSize = computed(() => Math.max(30, props.size));
const renderViewportSize = computed(() => Math.round(viewportSize.value * renderPaddingFactor));
const renderViewportOffset = computed(() => (renderViewportSize.value - viewportSize.value) / 2);

function createCubeGeometry(cubeRadius) {
    return [
        [-cubeRadius, -cubeRadius, -cubeRadius],
        [cubeRadius, -cubeRadius, -cubeRadius],
        [cubeRadius, cubeRadius, -cubeRadius],
        [-cubeRadius, cubeRadius, -cubeRadius],
        [-cubeRadius, -cubeRadius, cubeRadius],
        [cubeRadius, -cubeRadius, cubeRadius],
        [cubeRadius, cubeRadius, cubeRadius],
        [-cubeRadius, cubeRadius, cubeRadius],
    ];
}

function createCubeEdges() {
    return [
        [0, 1], [1, 2], [2, 3], [3, 0],
        [4, 5], [5, 6], [6, 7], [7, 4],
        [0, 4], [1, 5], [2, 6], [3, 7],
    ];
}

function createOrbitingElectronGeometry(orbitRadius, orbitCount) {
    const orbitingElectronSphere = [];

    for (let orbitIndex = 0; orbitIndex < orbitCount; orbitIndex += 1) {
        orbitingElectronSphere.push({
            orbitTilt: orbitIndex * (Math.PI / orbitCount),
            orbitRadius,
            orbitPhaseOffset: orbitIndex * 2.12,
        });
    }

    return orbitingElectronSphere;
}

function createVisualMaterials() {
    return {
        cubeOutlineColor: basePalette.outline,
        cubeGlowColor: basePalette.accentBlue,
        cubeFillColor: 'rgba(88, 166, 255, 0.05)',
        orbitColor: basePalette.accentBlue,
        nucleusColor: basePalette.outline,
        electronColor: basePalette.accentBlue,
    };
}

function assertRenderEnvironment(targetCanvas, sceneMaterials) {
    console.assert(targetCanvas instanceof HTMLCanvasElement, 'Canvas element is not available for logo rendering.');
    console.assert(Boolean(sceneMaterials?.cubeOutlineColor), 'Logo material palette is not initialized.');

    if (!(targetCanvas instanceof HTMLCanvasElement)) {
        throw new Error('Canvas not ready for Lawangsewu logo animation.');
    }

    if (!sceneMaterials?.cubeOutlineColor) {
        throw new Error('Materials not ready for Lawangsewu logo animation.');
    }
}

function createRotationMatrix(rotationAngleX, rotationAngleY, rotationAngleZ) {
    const sinX = Math.sin(rotationAngleX);
    const cosX = Math.cos(rotationAngleX);
    const sinY = Math.sin(rotationAngleY);
    const cosY = Math.cos(rotationAngleY);
    const sinZ = Math.sin(rotationAngleZ);
    const cosZ = Math.cos(rotationAngleZ);

    return {
        rotatePoint(point3D) {
            const [originalX, originalY, originalZ] = point3D;

            const yAfterX = originalY * cosX - originalZ * sinX;
            const zAfterX = originalY * sinX + originalZ * cosX;
            const xAfterY = originalX * cosY + zAfterX * sinY;
            const zAfterY = -originalX * sinY + zAfterX * cosY;
            const xAfterZ = xAfterY * cosZ - yAfterX * sinZ;
            const yAfterZ = xAfterY * sinZ + yAfterX * cosZ;

            return [xAfterZ, yAfterZ, zAfterY];
        },
    };
}

function projectPointToCanvas(point3D, perspectiveDepth) {
    const [pointX, pointY, pointZ] = point3D;
    const perspectiveScale = perspectiveDepth / (perspectiveDepth - pointZ);
    return [pointX * perspectiveScale, pointY * perspectiveScale, pointZ];
}

function drawGlowCircle(canvasContext, centerX, centerY, radius, color, intensityMultiplier) {
    const gradientRadius = radius * (3.6 + intensityMultiplier * 0.9);
    const glowGradient = canvasContext.createRadialGradient(centerX, centerY, 0, centerX, centerY, gradientRadius);
    glowGradient.addColorStop(0, `${color}d8`);
    glowGradient.addColorStop(0.45, `${color}68`);
    glowGradient.addColorStop(1, `${color}00`);

    canvasContext.fillStyle = glowGradient;
    canvasContext.beginPath();
    canvasContext.arc(centerX, centerY, gradientRadius, 0, Math.PI * 2);
    canvasContext.fill();
}

function drawLineSegment(canvasContext, fromPoint, toPoint, strokeColor, lineThickness) {
    canvasContext.strokeStyle = strokeColor;
    canvasContext.lineWidth = lineThickness;
    canvasContext.beginPath();
    canvasContext.moveTo(fromPoint[0], fromPoint[1]);
    canvasContext.lineTo(toPoint[0], toPoint[1]);
    canvasContext.stroke();
}

function createProjectedCubeVertices(cubeRadius, rotationMatrix, floatingOffsetY) {
    return createCubeGeometry(cubeRadius)
        .map((vertex) => rotationMatrix.rotatePoint(vertex))
        .map((rotatedVertex) => projectPointToCanvas(rotatedVertex, 170))
        .map((projectedPoint) => [projectedPoint[0], projectedPoint[1] + floatingOffsetY, projectedPoint[2]]);
}

function drawTransparentCubeFaces(canvasContext, projectedCubeVertices, sceneMaterials, faceOpacity) {
    const visibleFaces = [
        [4, 5, 6, 7],
        [1, 5, 6, 2],
        [3, 2, 6, 7],
    ];

    canvasContext.fillStyle = sceneMaterials.cubeFillColor;
    canvasContext.globalAlpha = faceOpacity;

    visibleFaces.forEach((faceIndexes) => {
        canvasContext.beginPath();
        canvasContext.moveTo(projectedCubeVertices[faceIndexes[0]][0], projectedCubeVertices[faceIndexes[0]][1]);
        faceIndexes.slice(1).forEach((faceIndex) => {
            canvasContext.lineTo(projectedCubeVertices[faceIndex][0], projectedCubeVertices[faceIndex][1]);
        });
        canvasContext.closePath();
        canvasContext.fill();
    });

    canvasContext.globalAlpha = 1;
}

function drawTransparentCubeOutline(canvasContext, projectedCubeVertices, cubeEdges, sceneMaterials, interactionMix) {
    canvasContext.shadowBlur = 5 + interactionMix * 10;
    canvasContext.shadowColor = sceneMaterials.cubeGlowColor;

    cubeEdges.forEach(([startIndex, endIndex]) => {
        const lineStartPoint = projectedCubeVertices[startIndex];
        const lineEndPoint = projectedCubeVertices[endIndex];
        const depthFactor = (lineStartPoint[2] + lineEndPoint[2]) / 2;
        const edgeOpacity = 0.22 + ((depthFactor + 20) / 80) * 0.22 + interactionMix * 0.16;

        drawLineSegment(
            canvasContext,
            lineStartPoint,
            lineEndPoint,
            `rgba(201, 209, 217, ${Math.min(0.88, edgeOpacity)})`,
            0.92,
        );
    });

    canvasContext.shadowBlur = 0;
}

function drawAtomCore(canvasContext, sceneGeometry, sceneMaterials, floatingOffsetY, interactionMix) {
    const nucleusX = 0;
    const nucleusY = floatingOffsetY;
    const glowStrength = 0.62 + interactionMix * 0.9;

    canvasContext.shadowBlur = 7 + interactionMix * 12;
    canvasContext.shadowColor = sceneMaterials.orbitColor;

    sceneGeometry.orbitingElectronSphere.forEach((orbitDefinition) => {
        const orbitalProgress = orbitRotationPhase.value * (1.12 + interactionMix * 1.75) + orbitDefinition.orbitPhaseOffset;
        const orbitalX = Math.cos(orbitalProgress) * orbitDefinition.orbitRadius;
        const orbitalY = Math.sin(orbitalProgress) * orbitDefinition.orbitRadius;

        const orbitTiltRotation = createRotationMatrix(orbitDefinition.orbitTilt, orbitDefinition.orbitTilt * 0.58, 0);
        const rotatedElectronPoint = orbitTiltRotation.rotatePoint([orbitalX, orbitalY, 0]);
        const projectedElectronPoint = projectPointToCanvas(rotatedElectronPoint, 155);

        const electronX = projectedElectronPoint[0];
        const electronY = projectedElectronPoint[1] + floatingOffsetY;
        const electronRadius = 1.2 + interactionMix * 0.35;

        drawLineSegment(
            canvasContext,
            [nucleusX, nucleusY],
            [electronX, electronY],
            `rgba(88, 166, 255, ${0.08 + interactionMix * 0.14})`,
            0.7,
        );

        drawGlowCircle(canvasContext, electronX, electronY, electronRadius, sceneMaterials.electronColor, glowStrength);
        canvasContext.fillStyle = sceneMaterials.electronColor;
        canvasContext.beginPath();
        canvasContext.arc(electronX, electronY, electronRadius, 0, Math.PI * 2);
        canvasContext.fill();
    });

    drawGlowCircle(canvasContext, nucleusX, nucleusY, 2 + interactionMix * 0.35, sceneMaterials.electronColor, 0.65 + interactionMix * 0.8);

    canvasContext.shadowBlur = 0;
    canvasContext.fillStyle = sceneMaterials.nucleusColor;
    canvasContext.beginPath();
    canvasContext.arc(nucleusX, nucleusY, 1.9 + interactionMix * 0.15, 0, Math.PI * 2);
    canvasContext.fill();
}

function drawSceneFrame(canvasContext, sceneGeometry, sceneMaterials, canvasSize) {
    canvasContext.setTransform(1, 0, 0, 1, 0, 0);
    canvasContext.clearRect(0, 0, canvasSize, canvasSize);
    canvasContext.translate(canvasSize / 2, canvasSize / 2);

    const floatingOffsetY = Math.sin(floatingPhase.value) * 1.1;
    const cubeRotation = createRotationMatrix(
        0.62 + pointerTiltY.value * 0.18,
        0.44 + orbitRotationPhase.value * 0.28 + pointerTiltX.value * 0.26,
        0.16 + pointerTiltX.value * 0.05,
    );

    const projectedCubeVertices = createProjectedCubeVertices(8.4 + interactionSpeedFactor.value * 0.85, cubeRotation, floatingOffsetY);

    drawTransparentCubeFaces(canvasContext, projectedCubeVertices, sceneMaterials, 0.22 + interactionSpeedFactor.value * 0.08);
    drawAtomCore(canvasContext, sceneGeometry, sceneMaterials, floatingOffsetY, interactionSpeedFactor.value);
    drawTransparentCubeOutline(canvasContext, projectedCubeVertices, sceneGeometry.cubeEdges, sceneMaterials, interactionSpeedFactor.value);

    canvasContext.setTransform(1, 0, 0, 1, 0, 0);
}

function startAnimationLoop(targetCanvas) {
    const canvasContext = targetCanvas.getContext('2d', { alpha: true });
    const sceneMaterials = createVisualMaterials();

    assertRenderEnvironment(targetCanvas, sceneMaterials);

    const sceneGeometry = {
        cubeEdges: createCubeEdges(),
        orbitingElectronSphere: createOrbitingElectronGeometry(7.6, 3),
    };

    const renderFrame = () => {
        const interactionTransitionStep = 0.1;
        const pointerTransitionStep = 0.08;
        const idleOrbitSpeed = 0.016;
        const hoverOrbitBoost = 0.02;

        interactionSpeedFactor.value += (targetInteractionSpeedFactor.value - interactionSpeedFactor.value) * interactionTransitionStep;
        pointerTiltX.value += (targetPointerTiltX.value - pointerTiltX.value) * pointerTransitionStep;
        pointerTiltY.value += (targetPointerTiltY.value - pointerTiltY.value) * pointerTransitionStep;

        floatingPhase.value += 0.03;
        orbitRotationPhase.value += idleOrbitSpeed + hoverOrbitBoost * interactionSpeedFactor.value;

        drawSceneFrame(canvasContext, sceneGeometry, sceneMaterials, renderViewportSize.value);
        animationFrameHandle.value = window.requestAnimationFrame(renderFrame);
    };

    animationFrameHandle.value = window.requestAnimationFrame(renderFrame);
}

function setHoverState(nextHoverState) {
    targetInteractionSpeedFactor.value = nextHoverState ? 1 : 0;

    if (!nextHoverState) {
        targetPointerTiltX.value = 0;
        targetPointerTiltY.value = 0;
    }
}

function handlePointerEnter() {
    setHoverState(true);
}

function handlePointerLeave() {
    setHoverState(false);
}

function handlePointerMove(event) {
    if (!(event.currentTarget instanceof HTMLElement)) {
        return;
    }

    const targetBounds = event.currentTarget.getBoundingClientRect();
    const normalizedPointerX = ((event.clientX - targetBounds.left) / targetBounds.width) * 2 - 1;
    const normalizedPointerY = ((event.clientY - targetBounds.top) / targetBounds.height) * 2 - 1;

    targetPointerTiltX.value = normalizedPointerX * 0.32;
    targetPointerTiltY.value = normalizedPointerY * 0.22;
}

onMounted(() => {
    if (!canvasElement.value) {
        return;
    }

    canvasElement.value.width = renderViewportSize.value;
    canvasElement.value.height = renderViewportSize.value;

    // TODO: sync logo motion intensity with authenticated user presence state.
    startAnimationLoop(canvasElement.value);
});

onBeforeUnmount(() => {
    if (animationFrameHandle.value) {
        window.cancelAnimationFrame(animationFrameHandle.value);
    }
});
</script>

<template>
    <div
        class="logo-canvas-shell"
        :style="{ width: `${viewportSize}px`, height: `${viewportSize}px` }"
        @pointerenter="handlePointerEnter"
        @pointerleave="handlePointerLeave"
        @pointermove="handlePointerMove"
    >
        <!-- WARNING: Keep canvas size compact to prevent battery drain on mobile devices. -->
        <canvas
            ref="canvasElement"
            class="logo-canvas"
            :width="renderViewportSize"
            :height="renderViewportSize"
            :style="{
                width: `${renderViewportSize}px`,
                height: `${renderViewportSize}px`,
                marginLeft: `-${renderViewportOffset}px`,
                marginTop: `-${renderViewportOffset}px`,
            }"
            aria-label="Lawang Sewu Atom Cube Logo"
            role="img"
        />
    </div>
</template>

<style scoped>
.logo-canvas-shell {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    box-shadow: none;
    transition: transform 260ms ease-in-out;
    transform-origin: center;
    overflow: visible;
}

.logo-canvas-shell:hover {
    transform: translateY(-1px);
}

.logo-canvas {
    display: block;
    background: transparent;
    filter: drop-shadow(0 0 1px rgba(88, 166, 255, 0.08));
    transition: filter 260ms ease-in-out;
    pointer-events: none;
}

.logo-canvas-shell:hover .logo-canvas {
    filter: drop-shadow(0 0 8px rgba(88, 166, 255, 0.32)) drop-shadow(0 0 18px rgba(88, 166, 255, 0.18));
}
</style>
