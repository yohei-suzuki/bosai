import pychatwork as ch
#import os

#folder = os.getcwd()
#print(folder)
f = open("saigai.txt", 'r', encoding='UTF-8')
data = f.read()
f.close()

txt = data.split("----\n")
print(len(txt))
i = len(txt) - 2
print(txt[i])

#
g = txt[i].split('\n')
#g[1] = g[1].replace('（Ｈ２７）', '')
print(g[2])

f = open("up.txt", 'r')
up = f.read()
f.close()
h = up.split('\n')
#h[1] = h[1].replace('（Ｈ２７）', '')
print(h[2])

if g[0] == h[0]:
    print("onaji")
else:
    #テキストファイルに出力
    f = open("up.txt", 'w')
    f.write(txt[i])
    f.close
    #chatworkに投稿
    client = ch.ChatworkClient("3dc74cf3c91c5a061a996339cf3ed7ee")
    client.post_messages(room_id="255128842", message=txt[i]) #my
    client.post_messages(room_id="255128855", message=txt[i]) #my

