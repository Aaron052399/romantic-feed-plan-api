const controllerUrl = '/api/NoticeSwitch/'

export function getNoticeSwitch(params: anyObj = {}) {
    return Http.fetch({
        url: controllerUrl + 'index',
        method: 'GET',
        params: params,
    })
}

export function postNoticeSwitch(body: anyObj = {}) {
    return Http.$fetch(
        {
            url: controllerUrl + 'index',
            method: 'POST',
            body: body,
        },
        {
            showSuccessMessage: true,
        }
    )
}
