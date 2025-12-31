import createAxios, { getUrl } from '/@/utils/axios'
const controllerUrl = '/admin/routine.Dataimport/'
import { useAdminInfo } from '/@/stores/adminInfo'

export function add() {
    return createAxios({
        url: controllerUrl + 'add',
        method: 'get',
    })
}

export function downloadImportTemplate(table: string) {
    const adminInfo = useAdminInfo()
    return getUrl() + controllerUrl + 'downloadImportTemplate?server=1&table=' + table + '&batoken=' + adminInfo.getToken()
}

export function handleXls(table: string, url: string) {
    return createAxios({
        url: controllerUrl + 'handleXls',
        method: 'get',
        params: {
            file: url,
            table: table,
        },
    })
}

export function importXls(table: string, url: string) {
    return createAxios(
        {
            url: controllerUrl + 'handleXls',
            method: 'post',
            params: {
                file: url,
                table: table,
            },
        },
        {
            showSuccessMessage: true,
        }
    )
}
