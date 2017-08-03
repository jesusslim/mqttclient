<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/2
 * Time: 下午9:33
 */

namespace mqttclient\src\consts;


class Options
{

    //fsock超时
    const FSOCK_TIMEOUT = 'FSOCK_TIMEOUT';

    //stream超时
    const STREAM_TIMEOUT = 'STREAM_TIMEOUT';

    //ping重复发起间隔
    const PING_INTERVAL_SECONDS = 'PING_INTERVAL_SECONDS';

    //subscribe进程执行间隔 微秒
    const PROCESS_INTERVAL_MICRO_SECONDS = 'PROCESS_INTERVAL_MICRO_SECONDS';

    //重连尝试次数
    const RECONNECT_TIMES = 'RECONNECT_TIMES';

    //重连尝试间隔
    const RECONNECT_TIME_INTERVAL = 'RECONNECT_TIME_INTERVAL';

}