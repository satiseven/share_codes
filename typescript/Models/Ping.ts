import { DateTime } from 'luxon'
import {
  BaseModel,
  BelongsTo,
  belongsTo,
  column,
  computed,
  HasMany,
  hasMany,
} from '@ioc:Adonis/Lucid/Orm'
import Email from './Email'
import PingEvent from './PingEvent'
import User from './User'
import PingSequence from './PingSequence'

export default class Ping extends BaseModel {
  @column({ isPrimary: true })
  public id: string

  @column()
  public emailId: string

  @column()
  public userId: string

  @column()
  public pingSequenceId: string

  @column()
  public name: string

  @column.dateTime({ autoCreate: true })
  public createdAt: DateTime

  @column.dateTime({ autoCreate: true, autoUpdate: true })
  public updatedAt: DateTime

  @belongsTo(() => Email)
  public email: BelongsTo<typeof Email>

  @belongsTo(() => User)
  public user: BelongsTo<typeof User>

  @belongsTo(() => PingSequence)
  public pingSequence: BelongsTo<typeof PingSequence>

  @hasMany(() => PingEvent)
  public events: HasMany<typeof PingEvent>

  @computed()
  public get time() {
    return this.createdAt.toRelative()
  }

  public get serializedPingInfo() {
    const { id, userId, emailId, name, time } = this

    return {
      id,
      userId,
      emailId,
      name,
      time,
    }
  }
}
