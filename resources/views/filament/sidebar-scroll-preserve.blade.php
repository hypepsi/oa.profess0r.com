<script>
    // 全局保存和恢复左侧导航栏（sidebar）的滚动位置
    (function() {
        const scrollKey = 'filament-sidebar-scroll';
        let isRestoring = false;
        let sidebarElement = null;
        
        // 获取左侧导航栏元素
        function getSidebarElement() {
            if (sidebarElement) return sidebarElement;
            
            // 尝试多种选择器
            const selectors = [
                '.fi-sidebar-nav',
                '.fi-sidebar nav',
                '[data-sidebar]',
                '.fi-sidebar',
                'aside nav',
                'aside[role="navigation"]',
                'aside'
            ];
            
            for (const selector of selectors) {
                const element = document.querySelector(selector);
                if (element && element.scrollHeight > element.clientHeight) {
                    sidebarElement = element;
                    return element;
                }
            }
            
            return null;
        }
        
        // 保存滚动位置
        function saveScrollPosition() {
            if (isRestoring) return;
            const sidebar = getSidebarElement();
            if (sidebar) {
                const scrollTop = sidebar.scrollTop;
                sessionStorage.setItem(scrollKey, scrollTop.toString());
            }
        }
        
        // 恢复滚动位置
        function restoreScrollPosition() {
            const sidebar = getSidebarElement();
            if (!sidebar) return;
            
            const savedScroll = sessionStorage.getItem(scrollKey);
            if (savedScroll !== null) {
                isRestoring = true;
                const scrollTop = parseInt(savedScroll, 10);
                
                requestAnimationFrame(() => {
                    sidebar.scrollTop = scrollTop;
                    setTimeout(() => {
                        isRestoring = false;
                    }, 150);
                });
            }
        }
        
        // 监听左侧导航栏的滚动事件
        function setupSidebarScrollListener() {
            const sidebar = getSidebarElement();
            if (!sidebar) {
                setTimeout(setupSidebarScrollListener, 200);
                return;
            }
            
            let scrollTimeout;
            sidebar.addEventListener('scroll', function() {
                if (isRestoring) return;
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(saveScrollPosition, 200);
            }, { passive: true });
        }
        
        // 初始化
        function initScrollRestore() {
            setupSidebarScrollListener();
            
            // 延迟恢复，确保导航栏已完全渲染
            setTimeout(() => {
                restoreScrollPosition();
            }, 500);
            
            // Livewire 更新后也恢复
            if (window.Livewire) {
                window.Livewire.hook('morph.updated', () => {
                    setTimeout(restoreScrollPosition, 300);
                });
            }
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initScrollRestore);
        } else {
            initScrollRestore();
        }
        
        // 页面卸载前保存
        window.addEventListener('beforeunload', saveScrollPosition);
        
        // 页面可见性变化时保存
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                saveScrollPosition();
            }
        });
    })();
</script>



