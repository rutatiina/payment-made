
const Index = () => import('./components/l-limitless-bs4/Index');
const Form = () => import('./components/l-limitless-bs4/Form');
const Show = () => import('./components/l-limitless-bs4/Show');
const SideBarLeft = () => import('./components/l-limitless-bs4/SideBarLeft');
const SideBarRight = () => import('./components/l-limitless-bs4/SideBarRight');

const routes = [

    {
        path: '/payments-made',
        components: {
            default: Index,
            //'sidebar-left': ComponentSidebarLeft,
            //'sidebar-right': ComponentSidebarRight
        },
        meta: {
            title: 'Accounting :: Payments Made',
            metaTags: [
                {
                    name: 'description',
                    content: 'Payments Made'
                },
                {
                    property: 'og:description',
                    content: 'Payments Made'
                }
            ]
        }
    },
    {
        path: '/payments-made/create',
        components: {
            default: Form,
            //'sidebar-left': ComponentSidebarLeft,
            //'sidebar-right': ComponentSidebarRight
        },
        meta: {
            title: 'Accounting :: Payments Made :: Create',
            metaTags: [
                {
                    name: 'description',
                    content: 'Create Payments Made'
                },
                {
                    property: 'og:description',
                    content: 'Create Payments Made'
                }
            ]
        }
    },
    {
        path: '/payments-made/:id',
        components: {
            default: Show,
            'sidebar-left': SideBarLeft,
            'sidebar-right': SideBarRight
        },
        meta: {
            title: 'Accounting :: Payments Made',
            metaTags: [
                {
                    name: 'description',
                    content: 'Payments Made'
                },
                {
                    property: 'og:description',
                    content: 'Payments Made'
                }
            ]
        }
    },
    {
        path: '/payments-made/:id/copy',
        components: {
            default: Form,
        },
        meta: {
            title: 'Accounting :: Payments Made :: Copy',
            metaTags: [
                {
                    name: 'description',
                    content: 'Copy Payments Made'
                },
                {
                    property: 'og:description',
                    content: 'Copy Payments Made'
                }
            ]
        }
    },
    {
        path: '/payments-made/:id/edit',
        components: {
            default: Form,
        },
        meta: {
            title: 'Accounting :: Payments Made :: Edit',
            metaTags: [
                {
                    name: 'description',
                    content: 'Edit Payments Made'
                },
                {
                    property: 'og:description',
                    content: 'Edit Payments Made'
                }
            ]
        }
    }

]

export default routes
