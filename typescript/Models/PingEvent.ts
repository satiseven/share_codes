import { DateTime } from 'luxon'
import { BaseModel, BelongsTo, belongsTo, column } from '@ioc:Adonis/Lucid/Orm'
import Ping from './Ping'

export default class PingEvent extends BaseModel {
  @column({ isPrimary: true })
  public id: string

  @column()
  public pingId: string

  @column.dateTime({ autoCreate: true })
  public createdAt: DateTime

  @column.dateTime({ autoCreate: true, autoUpdate: true })
  public updatedAt: DateTime

  @belongsTo(() => Ping)
  public ping: BelongsTo<typeof Ping>
}
