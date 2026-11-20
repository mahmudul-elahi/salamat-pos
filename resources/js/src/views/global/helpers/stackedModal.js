// Bootstrap gives every modal the same z-index (1055, backdrop 1050). Open a
// second modal from inside the first — a delete confirmation raised from an
// edit modal, say — and it lands *underneath* the one that opened it, hidden
// behind that modal's backdrop.
//
// Bootstrap has no stacking support of its own, so each modal that opens while
// another is already showing has to lift itself (and its own backdrop) above
// the ones below.

const MODAL_Z_INDEX = 1055;
const BACKDROP_Z_INDEX = 1050;
const STEP = 20;

/**
 * Call immediately after `modalInstance.show()`, with the modal's element.
 * A no-op when nothing else is open, so the common case keeps Bootstrap's
 * own z-index and stylesheet overrides keep working.
 */
export const raiseStackedModal = (element) => {
    if (!element) return;

    // The modal being opened hasn't gained `.show` yet, so this counts only
    // the ones already on screen.
    const openCount = document.querySelectorAll('.modal.show').length;

    if (openCount === 0) return;

    const offset = openCount * STEP;

    element.style.zIndex = MODAL_Z_INDEX + offset;

    // Bootstrap appends the backdrop as it opens, so the newest one is last.
    const backdrops = document.querySelectorAll('.modal-backdrop');
    const backdrop = backdrops[backdrops.length - 1];

    if (backdrop) {
        backdrop.style.zIndex = BACKDROP_Z_INDEX + offset;
    }
};
