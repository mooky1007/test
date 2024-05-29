class FlexibleLayout {
    constructor(el) {
        this.el = el;
        this.observe();
        window.addEventListener('resize', () => this.render());
    }

    collectData() {
        const rows = [...this.el.querySelectorAll('.search_wrap_row')];

        return rows.reduce((acc, row, rowIdx, rowOrigin) => {
            const cols = [...row.querySelectorAll('.search_wrap_col')];

            cols.forEach((col, idx) => {
                const colspan = col.className.match(/col-(\d+)/)[1];
                const labelEl = col.querySelector('label');
                const colIdx = idx === 0 ? 0 : +cols[idx - 1].getAttribute('col-idx') + (+cols[idx - 1].className.match(/col-(\d+)/)[1] || 1);
                col.setAttribute('col-idx', colIdx);

                if (!acc[colIdx]) acc[colIdx] = [];
                acc[colIdx].push({ labelEl, colspan });
            });
            return acc;
        }, {});
    }

    observe() {
        const observer = new MutationObserver(() => this.render());
        observer.observe(this.el, {
            childList: true,
            subtree: true,
            characterDataOldValue: true,
        });

        this.render()
    }

    render() {
        Object.values(this.collectData()).forEach((labels) => {
            labels.forEach((labelObj) => (labelObj.labelEl.style.width = 'auto'));
            const maxWidth = Math.max(...labels.map((labelObj) => labelObj.labelEl.offsetWidth + 5));
            const minWidth = Math.min(...labels.map((labelObj) => labelObj.labelEl.closest('.search_wrap_col').offsetWidth / 2));

            labels.forEach((labelObj) => {
                labelObj.labelEl.style.width = `${maxWidth}px`;
                labelObj.labelEl.style.maxWidth = `${minWidth}px`;
            });
        });
    }
}
