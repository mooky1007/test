class LabelResizer {
    constructor(options) {
        this.el = options.wrapperEl;
        this.rowClassName = options.rowClassName || '.search_wrap_row';
        this.colClassName = options.colClassName || '.search_wrap_col';
        this.labelClassName = options.labelClassName || '.label_cell';
        this.contentClassName = options.contentClassName || '.content_cell';
        this.timer = null;

        this.observe();
    }

    collectData() {
        const rows = [...this.el.querySelectorAll(this.rowClassName)];

        const result = rows.reduce((acc, row, rowIdx, rowOrigin) => {
            const cols = [...row.querySelectorAll(`${this.colClassName}`)];

            cols.forEach((col, idx) => {
                const colspan = col.className.match(/col-(\d+)/)[1];
                const labelEl = col.querySelector(this.labelClassName);
                const colIdx = idx === 0 ? 0 : +cols[idx - 1].getAttribute('col-idx') + (+cols[idx - 1].className.match(/col-(\d+)/)[1] || 1);
                col.setAttribute('col-idx', colIdx);

                if (!acc[colIdx]) acc[colIdx] = [];
                acc[colIdx].push({ labelEl, colspan });
            });
            return acc;
        }, {});

        document.querySelectorAll('[col-idx]').forEach((el) => el.removeAttribute('col-idx'));
        return result;
    }

    observe() {
        // 동적으로 라벨내용이 변경되는 일이 없다면 해당 함수제거
        const observer = new MutationObserver(() => this.render());
        observer.observe(this.el, {
            childList: true,
            subtree: true,
            characterDataOldValue: true,
        });

        this.render();
    }

    render() {
        Object.values(this.collectData()).forEach((labels) => {
            labels.forEach((labelObj) => (labelObj.labelEl.style.width = 'auto'));
            const maxWidth = Math.max(...labels.map((labelObj) => labelObj.labelEl.offsetWidth + 5));
            const minWidth = Math.min(...labels.map((labelObj) => labelObj.labelEl.closest(this.colClassName).offsetWidth / 2));

            labels.forEach((labelObj) => {
                labelObj.labelEl.style.width = `${maxWidth}px`;
                labelObj.labelEl.style.maxWidth = `${minWidth}px`;
            });
        });
    }
}
