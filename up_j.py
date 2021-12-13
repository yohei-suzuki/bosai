import pychatwork as ch
#import os

#folder = os.getcwd()
#print(folder)
f = open("jisin.txt", 'r', encoding='UTF-8')
data = f.read()
f.close()

txt = data.split("----\n")
print(len(txt))
i = len(txt) - 2
print(txt[i])

#
g = txt[i].split('\n')
#g[1] = g[1].replace('（Ｈ２７）', '')
g_num = len(g) - 2
print(g_num)
if g_num == 0:
    quit()

print(g[2])

f = open("up_j.txt", 'r')
up = f.read()
f.close()
h = up.split('\n')
#h[1] = h[1].replace('（Ｈ２７）', '')
print(h[2])

if g[0] == h[0]:
    print("onaji")
else:
    #テキストファイルに出力
    f = open("up_j.txt", 'w')
    f.write(txt[i])
    f.close
    #chatworkに投稿
    head = "■■■■■■■■■■■■■■■\n"
    head = head + "■震源・震度に関する情報■\n"
    head = head + "■■■■■■■■■■■■■■■\n"
    txt[i] = head + txt[i]
    client = ch.ChatworkClient("3dc74cf3c91c5a061a996339cf3ed7ee")
    client.post_messages(room_id="255128842", message=txt[i]) #my
    client.post_messages(room_id="255128855", message=txt[i]) #my



