const actionMenuContext = (row) => {
    if (! row) {
        return null;
    }

    const hosts = [...row.querySelectorAll('[x-data]')];
    const host = hosts.find((element) => {
        const expression = element.getAttribute('x-data') ?? '';
        const trigger = element.querySelector(':scope > button');
        const menu = element.querySelector(':scope > [x-show="open"]');

        return /\bopen\s*:/.test(expression) && trigger && menu;
    });

    if (! host) {
        return null;
    }

    return {
        row,
        table: row.closest('table'),
        host,
        trigger: host.querySelector(':scope > button'),
        menu: host.querySelector(':scope > [x-show="open"]'),
    };
};

const menuIsOpen = (menu) => menu && window.getComputedStyle(menu).display !== 'none';

const positionActionMenu = (context) => {
    if (! context?.table || ! menuIsOpen(context.menu)) {
        context?.row?.classList.remove('erp-table-row-active');
        context?.menu?.classList.remove('erp-action-menu-centered');

        return;
    }

    const tableRect = context.table.getBoundingClientRect();
    const rowRect = context.row.getBoundingClientRect();
    const menuWidth = context.menu.offsetWidth;
    const menuHeight = context.menu.offsetHeight;
    const visibleLeft = Math.max(12, tableRect.left);
    const visibleRight = Math.min(window.innerWidth - 12, tableRect.right);
    const left = visibleLeft + Math.max(0, (visibleRight - visibleLeft - menuWidth) / 2);
    const top = rowRect.bottom + 6;

    context.row.classList.add('erp-table-row-active');
    context.menu.classList.add('erp-action-menu-centered');
    context.menu.style.setProperty('position', 'fixed', 'important');
    context.menu.style.setProperty('left', `${Math.min(left, window.innerWidth - menuWidth - 12)}px`, 'important');
    context.menu.style.setProperty('top', `${top}px`, 'important');
    context.menu.style.setProperty('right', 'auto', 'important');
};

const refreshActionRows = () => {
    document.querySelectorAll('tbody > tr').forEach((row) => {
        const context = actionMenuContext(row);

        if (context) {
            positionActionMenu(context);
        } else {
            row.classList.remove('erp-table-row-active');
        }
    });
};

const scheduleActionMenuRefresh = (context = null) => {
    window.requestAnimationFrame(() => {
        window.requestAnimationFrame(() => {
            if (context) {
                positionActionMenu(context);
            }

            refreshActionRows();
        });
    });
};

const closeOtherActionMenus = (selectedRow) => {
    document.querySelectorAll('tbody > tr').forEach((row) => {
        if (row === selectedRow) {
            return;
        }

        const context = actionMenuContext(row);

        if (context && menuIsOpen(context.menu)) {
            context.trigger.click();
            context.row.classList.remove('erp-table-row-active');
        }
    });
};

document.addEventListener('click', (event) => {
    const target = event.target instanceof Element ? event.target : null;
    const row = target?.closest('tbody > tr');
    const context = actionMenuContext(row);

    if (! context) {
        scheduleActionMenuRefresh();

        return;
    }

    const clickedTrigger = context.trigger.contains(target);
    const clickedInteractiveControl = target.closest('button, a, input, select, textarea, label, [role="button"], [contenteditable="true"]');

    if (! clickedInteractiveControl) {
        event.preventDefault();
        event.stopImmediatePropagation();
        closeOtherActionMenus(row);
        context.trigger.click();
    } else if (clickedTrigger) {
        closeOtherActionMenus(row);
    }

    scheduleActionMenuRefresh(context);
}, true);

window.addEventListener('resize', () => scheduleActionMenuRefresh());
document.addEventListener('livewire:navigated', () => scheduleActionMenuRefresh());

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => scheduleActionMenuRefresh(), { once: true });
} else {
    scheduleActionMenuRefresh();
}
