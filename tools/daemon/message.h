#ifndef _MESSAGE_H

#define _MESSAGE_H

#include "defs.h"

char *messages_array[MSG_MAX_COUNT][1] = {
	{MSG_WELCOME_TXT},
	{MSG_DAEMON_VER_TXT},
	{MSG_DAEMON_NAME_TXT},
	{MSG_ERROR_LISTEN_TXT},
	{MSG_SIG_CHLD_TXT},
	{MSG_SIG_PIPE_TXT},
	{MSG_ERROR_EINTR_TXT},
	{MSG_ERROR_ACCEPT_TXT},
	{MSG_START_CHILD_TXT},
	{MSG_ERROR_SOCKET_WR_TXT},
	{MSG_BYTES_WRITTEN_TXT},
	{MSG_ERROR_SOCKET_RD_TXT},
	{MSG_ERROR_SOCKET_EOF_TXT},
	{MSG_BYTES_READ_TXT},
	{MSG_HELO_CMD_TXT},
	{MSG_BAD_SYNTAX_TXT},
	{MSG_CMD_OK_TXT},
	{MSG_BYE_CMD_TXT},
	{MSG_EQ_CMD_TXT},
	{MSG_CONF_FILE_TXT},
	{MSG_MISSING_REG_DATA_TXT},
	{MSG_ERROR_BIND_TXT}
};

char *message(int message_number);

#endif
