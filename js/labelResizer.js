class LabelResizer {
    constructor(options) {
        this.el = options.wrapperEl;
        this.rowClassName = options.rowClassName || '.search_wrap_row';
        this.colClassName = options.colClassName || '.search_wrap_col';
        this.labelClassName = options.labelClassName || '.label_cell';
        this.contentClassName = options.contentClassName || '.content_cell';

        this.render();
    }

    collectData() {
        const rows = [...this.el.querySelectorAll(this.rowClassName)];

        const result = rows.reduce((acc, row) => {
            const cols = [...row.querySelectorAll(`${this.colClassName}`)];

            cols.forEach((col, idx) => {
                const colIdx = idx === 0 
                ? 0 
                : +cols[idx - 1].getAttribute('col-idx') + (+cols[idx - 1].className.match(/col-(\d+)/)[1] || 1);
                const colspan = col.className.match(/col-(\d+)/)[1];
                const el = col.querySelector(this.labelClassName);
                col.setAttribute('col-idx', colIdx);

                if (!acc[colIdx]) acc[colIdx] = [];
                acc[colIdx].push({ el, colspan });
            });
            return acc;
        }, {});

        this.el.querySelectorAll('[col-idx]').forEach((el) => el.removeAttribute('col-idx'));
        return result;
    }

    render() {
        Object.values(this.collectData()).forEach((data) => {
            data.forEach((obj) => (obj.el.style.width = 'auto'));
            const maxWidth = Math.max(...data.map((obj) => obj.el.offsetWidth + 5));
            const minWidth = Math.min(...data.map((obj) => obj.el.closest(this.colClassName).offsetWidth / 2));

            data.forEach((obj) => {
                obj.el.style.width = `${maxWidth}px`;
                obj.el.style.maxWidth = `${minWidth}px`;
            });
        });
    }
}