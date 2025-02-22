# -*- coding: utf-8 -*-
"""
Created on Sun Nov 24 19:33:53 2024

@author: 14221
"""

import torch
import torch.nn as nn
class AttentionModule_cbam_channel(nn.Module):
    def __init__(self,channel):
        super(AttentionModule_cbam_channel, self).__init__()
        self.max_pool =nn.AdaptiveMaxPool2d(1)
        self.avg_pool =nn.AdaptiveAvgPool2d(1)
        self.fc=nn.Sequential(
            nn.Linear(channel, channel // 16, bias=False),
            nn.ReLU(inplace=True),
            nn.Linear(channel // 16, channel, bias=False),
                )
        self.sigmod=nn.Sigmoid()
    def forward(self,x):
        b,c,h,w =x.size()
        max_pool_out=self.max_pool(x).view(b,c)
        avg_pool_out=self.avg_pool(x).view(b,c)
        
        max_fc_out=self.fc(max_pool_out)
        max_fc_out=self.fc(avg_pool_out)
        out=max_fc_out+max_fc_out
        out=self.sigmod(out).view(b, c, 1, 1)
        return out*x
class AttentionModule_cbam_spacial(nn.Module):
    def __init__(self):
        super(AttentionModule_cbam_spacial, self).__init__()
        self.conv=nn.Conv2d(2,1,kernel_size=3,padding=1)
        
        self.sigmod=nn.Sigmoid()
    def forward(self,x):
        b,c,h,w =x.size()
        max_pool_out,_=torch.max(x,dim=1,keepdim=True)
        mean_pool_out=torch.mean(x,dim=1,keepdim=True)
        pool_out=torch.cat([max_pool_out,mean_pool_out],dim=1)
        out=self.conv(pool_out)
        out=self.sigmod(out)
        return out*x
class AttentionModule_cbam(nn.Module):
    def __init__(self, channel):
        super(AttentionModule_cbam, self).__init__()
        self.channel_attention = AttentionModule_cbam_channel(channel)
        self.spacial_attention = AttentionModule_cbam_spacial()

    def forward(self, x):
        x=self.channel_attention(x)
        x=self.spacial_attention(x)
        return x 
class ConvBNReLU(nn.Sequential):
    def __init__(self, in_planes, out_planes, kernel_size=3, stride=1, groups=1):
        padding = (kernel_size - 1) // 2
        super(ConvBNReLU, self).__init__(
            nn.Conv2d(in_planes, out_planes, kernel_size, stride, padding, groups=groups, bias=False),
            nn.BatchNorm2d(out_planes),
            nn.ReLU(inplace=True)
        )


class DWSeparableConv(nn.Module):
    def __init__(self, inp, oup):
        super().__init__()
        self.dwc = ConvBNReLU(inp, inp, kernel_size=3, groups=inp)
        self.pwc = ConvBNReLU(inp, oup, kernel_size=1)

    def forward(self, x):
        x = self.dwc(x)
        x = self.pwc(x)

        return x


class SSEBlock(nn.Module):
    def __init__(self, inp, oup):
        super().__init__()
        out_channel = oup * 4
        self.pwc1 = ConvBNReLU(inp, oup, kernel_size=1)
        self.pwc2 = ConvBNReLU(oup, out_channel, kernel_size=1)
        self.dwc = DWSeparableConv(oup, out_channel)

    def forward(self, x):
        x = self.pwc1(x)
        out1 = self.pwc2(x)
        out2 = self.dwc(x)

        return torch.cat((out1, out2), 1)


class SlimModule(nn.Module):
    def __init__(self, inp, oup):
        super().__init__()
        hidden_dim = oup * 4
        out_channel = oup * 3
        self.sse1 = SSEBlock(inp, oup)
        self.sse2 = SSEBlock(hidden_dim * 2, oup)
        self.dwc = DWSeparableConv(hidden_dim * 2, out_channel)
        self.conv = ConvBNReLU(inp, hidden_dim * 2, kernel_size=1)

    def forward(self, x):
        out = self.sse1(x)
        out += self.conv(x)
        out = self.sse2(out)
        out = self.dwc(out)

        return out


class SlimNet(nn.Module):
    def __init__(self, num_classes):
        super().__init__()
        self.conv = ConvBNReLU(3, 96, kernel_size=7, stride=2)
        self.max_pool0 = nn.MaxPool2d(kernel_size=3, stride=2)

        self.module1 = SlimModule(96, 16)
        self.module2 = SlimModule(48, 32)
        self.module3 = SlimModule(96, 48)
        self.module4 = SlimModule(144, 64)
        self.attention_cbam = AttentionModule_cbam(144)
        self.max_pool1 = nn.MaxPool2d(kernel_size=3, stride=2)
        self.max_pool2 = nn.MaxPool2d(kernel_size=3, stride=2)
        self.max_pool3 = nn.MaxPool2d(kernel_size=3, stride=2)
        self.max_pool4 = nn.MaxPool2d(kernel_size=3, stride=2)
        
        self.gap = nn.AdaptiveAvgPool2d((1, 1))
        self.fc = nn.Linear(192, num_classes)
    def forward(self, x):
        x = self.max_pool0(self.conv(x))
        x = self.max_pool1(self.module1(x))
        x = self.max_pool2(self.module2(x))
        x = self.max_pool3(self.attention_cbam(self.module3(x)))
        x = self.max_pool4(self.module4(x))
        x = self.gap(x)
        x = torch.flatten(x, 1)
        x = self.fc(x)
        return x
